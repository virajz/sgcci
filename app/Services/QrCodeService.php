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
    // -------------------------------------------------------------------------
    // Exhibitor badge constants (badge.jpg — 1600×1048, landscape)
    // Left white panel: x=1..784, y=195..1048 (usable area below top logos)
    // Panel centre X = 392
    // -------------------------------------------------------------------------

    /** Centre X of the left panel (measured: panel spans x=1..784). */
    private const BADGE_PANEL_CENTER_X = 392;

    /** Circular photo: diameter and top-left corner (centered in panel). */
    private const BADGE_PHOTO_DIAMETER = 200;

    private const BADGE_PHOTO_X = 292;   // (392 - 200/2)

    private const BADGE_PHOTO_Y = 230;   // just below the top logo area

    /** Name text Y baseline (below photo bottom at y=430, +40 gap). */
    private const BADGE_NAME_Y = 490;

    /** Company text Y baseline. */
    private const BADGE_COMPANY_Y = 545;

    /** Stall text Y baseline. */
    private const BADGE_STALL_Y = 590;

    /** QR code size and top-left (centered in panel). */
    private const BADGE_QR_SIZE = 230;

    private const BADGE_QR_X = 277;     // (392 - 230/2)

    private const BADGE_QR_Y = 625;

    /** Max text width for left panel text (leave ~30px margin each side). */
    private const BADGE_TEXT_MAX_WIDTH = 560;

    /** Font sizes. */
    private const BADGE_NAME_FONT_MAX = 36;

    private const BADGE_NAME_FONT_MIN = 20;

    private const BADGE_COMPANY_FONT_MAX = 26;

    private const BADGE_COMPANY_FONT_MIN = 14;

    private const BADGE_STALL_FONT = 20;

    /** Colours. */
    private const BADGE_TEXT_DARK = '#1e2d5a';

    private const BADGE_QR_BG = [255, 255, 255];

    private const BADGE_QR_FG = [0, 0, 0];

    /**
     * Inner blue box pixel coordinates in creative.jpeg (1080×1920).
     * Measured precisely from the image.
     */
    private const BOX_X1 = 295;

    private const BOX_Y1 = 619;

    private const BOX_X2 = 782;

    private const BOX_Y2 = 1105;

    /** Padding inside the blue box so the QR doesn't touch the edges. */
    private const BOX_PADDING = 10;

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

    private const QR_BG = [255, 255, 255];

    private const QR_FG = [0, 0, 0];

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
     * Generate the exhibitor badge composite: badge.jpg with photo, name,
     * company, stall number(s), and the QR code placed on the left white panel.
     *
     * @param  string  $qrData  URL/text to encode in the QR
     * @param  string  $memberName  Full name of the badge holder
     * @param  string  $companyName  Brand / company name
     * @param  string  $stallNumbers  Stall number(s) e.g. "42, 43"
     * @param  string|null  $photoPath  Absolute path to the member's photo (optional)
     */
    public function generateBadgeImage(
        string $qrData,
        string $memberName,
        string $companyName,
        string $stallNumbers,
        ?string $photoPath = null,
    ): string {
        /** @var Imagick $badge */
        $badge = new Imagick(public_path('badge.jpg'));

        // --- Circular photo ---
        if ($photoPath && file_exists($photoPath)) {
            /** @var Imagick $photo */
            $photo = new Imagick($photoPath);

            // Square-crop first so the circle is symmetric
            $photo->cropThumbnailImage(self::BADGE_PHOTO_DIAMETER, self::BADGE_PHOTO_DIAMETER);
            $photo->setImageFormat('png');

            // Build a circular mask: white circle on black background
            /** @var Imagick $mask */
            $mask = new Imagick;
            $mask->newImage(self::BADGE_PHOTO_DIAMETER, self::BADGE_PHOTO_DIAMETER, new ImagickPixel('black'));
            $mask->setImageFormat('png');
            /** @var ImagickDraw $circle */
            $circle = new ImagickDraw;
            $circle->setFillColor(new ImagickPixel('white'));
            $r = self::BADGE_PHOTO_DIAMETER / 2;
            $circle->circle($r, $r, $r * 2, $r);
            $mask->drawImage($circle);

            // Enable alpha on photo, then use the mask as the alpha channel
            $photo->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            $photo->compositeImage($mask, Imagick::COMPOSITE_COPYOPACITY, 0, 0);

            $badge->compositeImage($photo, Imagick::COMPOSITE_OVER, self::BADGE_PHOTO_X, self::BADGE_PHOTO_Y);
        } else {
            // Draw a filled circle with initials as placeholder
            /** @var ImagickDraw $circleDraw */
            $circleDraw = new ImagickDraw;
            $circleDraw->setFillColor(new ImagickPixel('#d1d5db'));
            $cx = self::BADGE_PHOTO_X + self::BADGE_PHOTO_DIAMETER / 2;
            $cy = self::BADGE_PHOTO_Y + self::BADGE_PHOTO_DIAMETER / 2;
            $r = self::BADGE_PHOTO_DIAMETER / 2;
            $circleDraw->circle($cx, $cy, $cx + $r, $cy);
            $badge->drawImage($circleDraw);

            // Initials text
            $initials = collect(explode(' ', $memberName))
                ->take(2)
                ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                ->implode('');

            /** @var ImagickDraw $initDraw */
            $initDraw = new ImagickDraw;
            $initDraw->setFont(self::fontPath());
            $initDraw->setFontSize(80);
            $initDraw->setFillColor(new ImagickPixel('#6b7280'));
            $initDraw->setTextAlignment(Imagick::ALIGN_CENTER);
            $badge->annotateImage($initDraw, (int) $cx, (int) ($cy + 30), 0, $initials);
        }

        // --- QR code ---
        $qrPng = (new Writer(
            new ImageRenderer(
                new RendererStyle(self::BADGE_QR_SIZE, 0, null, null, Fill::uniformColor(
                    new Rgb(...self::BADGE_QR_BG),
                    new Rgb(...self::BADGE_QR_FG),
                )),
                new ImagickImageBackEnd
            )
        ))->writeString($qrData);

        /** @var Imagick $qrImage */
        $qrImage = new Imagick;
        $qrImage->readImageBlob($qrPng);
        $badge->compositeImage($qrImage, Imagick::COMPOSITE_OVER, self::BADGE_QR_X, self::BADGE_QR_Y);

        // --- Text: Name ---
        $nameText = mb_strtoupper($memberName);
        $nameFontSize = $this->fitTextToWidth($badge, $nameText, self::BADGE_NAME_FONT_MAX, self::BADGE_NAME_FONT_MIN, self::BADGE_TEXT_MAX_WIDTH);

        /** @var ImagickDraw $nameDraw */
        $nameDraw = new ImagickDraw;
        $nameDraw->setFont(self::fontPath());
        $nameDraw->setFontSize($nameFontSize);
        $nameDraw->setFillColor(new ImagickPixel(self::BADGE_TEXT_DARK));
        $nameDraw->setTextAlignment(Imagick::ALIGN_CENTER);
        $nameDraw->setTextAntialias(true);
        $badge->annotateImage($nameDraw, self::BADGE_PANEL_CENTER_X, self::BADGE_NAME_Y, 0, $nameText);

        // --- Text: Company ---
        $companyText = $companyName;
        $companyFontSize = $this->fitTextToWidth($badge, $companyText, self::BADGE_COMPANY_FONT_MAX, self::BADGE_COMPANY_FONT_MIN, self::BADGE_TEXT_MAX_WIDTH);

        /** @var ImagickDraw $companyDraw */
        $companyDraw = new ImagickDraw;
        $companyDraw->setFont(self::fontPath());
        $companyDraw->setFontSize($companyFontSize);
        $companyDraw->setFillColor(new ImagickPixel(self::BADGE_TEXT_DARK));
        $companyDraw->setTextAlignment(Imagick::ALIGN_CENTER);
        $companyDraw->setTextAntialias(true);
        $badge->annotateImage($companyDraw, self::BADGE_PANEL_CENTER_X, self::BADGE_COMPANY_Y, 0, $companyText);

        // --- Text: Stall ---
        /** @var ImagickDraw $stallDraw */
        $stallDraw = new ImagickDraw;
        $stallDraw->setFont(self::fontPath());
        $stallDraw->setFontSize(self::BADGE_STALL_FONT);
        $stallDraw->setFillColor(new ImagickPixel('#6b7280'));
        $stallDraw->setTextAlignment(Imagick::ALIGN_CENTER);
        $stallDraw->setTextAntialias(true);
        $badge->annotateImage($stallDraw, self::BADGE_PANEL_CENTER_X, self::BADGE_STALL_Y, 0, 'Stall: '.$stallNumbers);

        $badge->setImageFormat('jpeg');
        $badge->setImageCompressionQuality(92);

        return $badge->getImageBlob();
    }

    /**
     * Generate a walk-in visitor badge using the exhibitor badge template,
     * but without a photo (initials placeholder only).
     *
     * @param  string  $qrData  URL/text to encode in the QR
     * @param  string  $visitorName  Full name of the visitor
     * @param  string  $companyName  Company / organisation (empty string if none)
     * @param  string  $registrationCode  e.g. VIS-ABCDEF
     */
    public function generateWalkInBadgeImage(
        string $qrData,
        string $visitorName,
        string $companyName,
        string $registrationCode,
    ): string {
        /** @var Imagick $badge */
        $badge = new Imagick(public_path('badge.jpg'));

        // --- Initials placeholder circle ---
        /** @var ImagickDraw $circleDraw */
        $circleDraw = new ImagickDraw;
        $circleDraw->setFillColor(new ImagickPixel('#d1d5db'));
        $cx = self::BADGE_PHOTO_X + self::BADGE_PHOTO_DIAMETER / 2;
        $cy = self::BADGE_PHOTO_Y + self::BADGE_PHOTO_DIAMETER / 2;
        $r = self::BADGE_PHOTO_DIAMETER / 2;
        $circleDraw->circle($cx, $cy, $cx + $r, $cy);
        $badge->drawImage($circleDraw);

        $initials = collect(explode(' ', $visitorName))
            ->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->implode('');

        /** @var ImagickDraw $initDraw */
        $initDraw = new ImagickDraw;
        $initDraw->setFont(self::fontPath());
        $initDraw->setFontSize(80);
        $initDraw->setFillColor(new ImagickPixel('#6b7280'));
        $initDraw->setTextAlignment(Imagick::ALIGN_CENTER);
        $badge->annotateImage($initDraw, (int) $cx, (int) ($cy + 30), 0, $initials);

        // --- QR code ---
        $qrPng = (new Writer(
            new ImageRenderer(
                new RendererStyle(self::BADGE_QR_SIZE, 0, null, null, Fill::uniformColor(
                    new Rgb(...self::BADGE_QR_BG),
                    new Rgb(...self::BADGE_QR_FG),
                )),
                new ImagickImageBackEnd
            )
        ))->writeString($qrData);

        /** @var Imagick $qrImage */
        $qrImage = new Imagick;
        $qrImage->readImageBlob($qrPng);
        $badge->compositeImage($qrImage, Imagick::COMPOSITE_OVER, self::BADGE_QR_X, self::BADGE_QR_Y);

        // --- Text: Name ---
        $nameText = mb_strtoupper($visitorName);
        $nameFontSize = $this->fitTextToWidth($badge, $nameText, self::BADGE_NAME_FONT_MAX, self::BADGE_NAME_FONT_MIN, self::BADGE_TEXT_MAX_WIDTH);

        /** @var ImagickDraw $nameDraw */
        $nameDraw = new ImagickDraw;
        $nameDraw->setFont(self::fontPath());
        $nameDraw->setFontSize($nameFontSize);
        $nameDraw->setFillColor(new ImagickPixel(self::BADGE_TEXT_DARK));
        $nameDraw->setTextAlignment(Imagick::ALIGN_CENTER);
        $nameDraw->setTextAntialias(true);
        $badge->annotateImage($nameDraw, self::BADGE_PANEL_CENTER_X, self::BADGE_NAME_Y, 0, $nameText);

        // --- Text: Company ---
        $companyText = $companyName ?: 'Visitor';
        $companyFontSize = $this->fitTextToWidth($badge, $companyText, self::BADGE_COMPANY_FONT_MAX, self::BADGE_COMPANY_FONT_MIN, self::BADGE_TEXT_MAX_WIDTH);

        /** @var ImagickDraw $companyDraw */
        $companyDraw = new ImagickDraw;
        $companyDraw->setFont(self::fontPath());
        $companyDraw->setFontSize($companyFontSize);
        $companyDraw->setFillColor(new ImagickPixel(self::BADGE_TEXT_DARK));
        $companyDraw->setTextAlignment(Imagick::ALIGN_CENTER);
        $companyDraw->setTextAntialias(true);
        $badge->annotateImage($companyDraw, self::BADGE_PANEL_CENTER_X, self::BADGE_COMPANY_Y, 0, $companyText);

        // --- Text: Registration Code ---
        /** @var ImagickDraw $codeDraw */
        $codeDraw = new ImagickDraw;
        $codeDraw->setFont(self::fontPath());
        $codeDraw->setFontSize(self::BADGE_STALL_FONT);
        $codeDraw->setFillColor(new ImagickPixel('#6b7280'));
        $codeDraw->setTextAlignment(Imagick::ALIGN_CENTER);
        $codeDraw->setTextAntialias(true);
        $badge->annotateImage($codeDraw, self::BADGE_PANEL_CENTER_X, self::BADGE_STALL_Y, 0, $registrationCode);

        $badge->setImageFormat('jpeg');
        $badge->setImageCompressionQuality(92);

        return $badge->getImageBlob();
    }

    /**
     * Find the largest font size where the text fits within the given max width.
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

    /**
     * Find the largest font size (between $max and $min) where $text fits within $maxWidth pixels.
     */
    private function fitTextToWidth(Imagick $img, string $text, int $max, int $min, int $maxWidth): int
    {
        /** @var ImagickDraw $draw */
        $draw = new ImagickDraw;
        $draw->setFont(self::fontPath());

        for ($size = $max; $size >= $min; $size -= 1) {
            $draw->setFontSize($size);
            $metrics = $img->queryFontMetrics($draw, $text);
            if ($metrics['textWidth'] <= $maxWidth) {
                return $size;
            }
        }

        return $min;
    }
}
