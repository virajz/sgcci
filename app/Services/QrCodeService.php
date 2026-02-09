<?php

namespace App\Services;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    /**
     * Generate an SVG QR code for the given data string.
     */
    public function generateSvg(string $data, int $size = 200): string
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle($size, 0, null, null, Fill::uniformColor(
                    new Rgb(255, 255, 255),
                    new Rgb(45, 55, 72),
                )),
                new SvgImageBackEnd
            )
        ))->writeString($data);

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }
}
