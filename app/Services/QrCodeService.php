<?php

namespace App\Services;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Imagick;
use ImagickDraw;
use ImagickPixel;

class QrCodeService
{
    /**
     * Inner blue box pixel coordinates in creative.jpeg (1080×1920).
     * Measured precisely from the image.
     */
    private const BOX_X1 = 295;

    private const BOX_Y1 = 619;

    private const BOX_X2 = 782;

    private const BOX_Y2 = 1105;

    /** Padding inside the blue box so the QR doesn't touch the edges. */
    private const BOX_PADDING = 28;

    /** Y baseline for the visitor name (below "REGISTRATION" which ends at ~1289). */
    private const NAME_Y = 1390;

    /** Max font size for the name. Will shrink for long names. */
    private const NAME_FONT_MAX = 58;

    /** Minimum font size allowed. */
    private const NAME_FONT_MIN = 36;

    /** Usable width for the name text (box width). */
    private const NAME_MAX_WIDTH = 450;

    /**
     * Font file for the visitor name, bundled with the project so it is
     * available on every server regardless of installed system fonts.
     */
    private const FONT_RELATIVE_PATH = 'resources/fonts/VerdanaBold.ttf';

    /** Blue box fill colour — used as QR background so it blends in. */
    private const QR_BG = [57, 49, 134];

    /** QR module colour. */
    private const QR_FG = [255, 255, 255];

    private static function fontPath(): string
    {
        return base_path(self::FONT_RELATIVE_PATH);
    }

    /**
     * Generate an SVG QR code for the web preview.
     * Uses the blue box colours so it blends with the creative background.
     */
    public function generateSvg(string $data, int $size = 200): string
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle($size, 0, null, null, Fill::uniformColor(
                    new Rgb(...self::QR_BG),
                    new Rgb(...self::QR_FG),
                )),
                new SvgImageBackEnd
            )
        ))->writeString($data);

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }

    /**
     * Generate a plain PNG QR code (black on white, for generic use).
     */
    public function generatePng(string $data, int $size = 800): string
    {
        return (new Writer(
            new ImageRenderer(
                new RendererStyle($size, 4, null, null, Fill::uniformColor(
                    new Rgb(255, 255, 255),
                    new Rgb(0, 0, 0)
                )),
                new ImagickImageBackEnd
            )
        ))->writeString($data);
    }

    /**
     * Generate the full visitor pass composite: creative.jpeg with the QR code
     * placed inside the blue box and the visitor name printed below.
     */
    public function generateVisitorPassImage(string $qrData, string $visitorName): string
    {
        /** @var Imagick $creative */
        $creative = new Imagick(public_path('creative.jpeg'));

        // --- QR code ---
        $boxW = self::BOX_X2 - self::BOX_X1;
        $boxH = self::BOX_Y2 - self::BOX_Y1;
        $qrSize = min($boxW, $boxH) - (self::BOX_PADDING * 2);

        $qrPng = (new Writer(
            new ImageRenderer(
                new RendererStyle($qrSize, 0, null, null, Fill::uniformColor(
                    new Rgb(...self::QR_BG),
                    new Rgb(...self::QR_FG),
                )),
                new ImagickImageBackEnd
            )
        ))->writeString($qrData);

        /** @var Imagick $qrImage */
        $qrImage = new Imagick;
        $qrImage->readImageBlob($qrPng);

        // Center the QR inside the blue box
        $qrX = self::BOX_X1 + intval(($boxW - $qrSize) / 2);
        $qrY = self::BOX_Y1 + intval(($boxH - $qrSize) / 2);

        $creative->compositeImage($qrImage, Imagick::COMPOSITE_OVER, $qrX, $qrY);

        // --- Visitor name ---
        $nameText = mb_strtoupper($visitorName);
        $fontSize = $this->fitFontSize($creative, $nameText);

        /** @var ImagickDraw $draw */
        $draw = new ImagickDraw;
        $draw->setFont(self::fontPath());
        $draw->setFontSize($fontSize);
        /** @var ImagickPixel $colour */
        $colour = new ImagickPixel('#39318a');
        $draw->setFillColor($colour);
        $draw->setTextAlignment(Imagick::ALIGN_CENTER);
        $draw->setTextAntialias(true);

        $creative->annotateImage($draw, 540, self::NAME_Y, 0, $nameText);

        $creative->setImageFormat('jpeg');
        $creative->setImageCompressionQuality(92);

        return $creative->getImageBlob();
    }

    /**
     * Find the largest font size where the text fits within NAME_MAX_WIDTH.
     */
    private function fitFontSize(Imagick $img, string $text): int
    {
        /** @var ImagickDraw $draw */
        $draw = new ImagickDraw;
        $draw->setFont(self::fontPath());

        for ($size = self::NAME_FONT_MAX; $size >= self::NAME_FONT_MIN; $size -= 2) {
            $draw->setFontSize($size);
            $metrics = $img->queryFontMetrics($draw, $text);
            if ($metrics['textWidth'] <= self::NAME_MAX_WIDTH) {
                return $size;
            }
        }

        return self::NAME_FONT_MIN;
    }
}
