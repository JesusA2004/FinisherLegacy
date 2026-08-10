<?php

namespace App\Services;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Pure SVG QR generation — no model coupling. LegacyCodeQrService (plates)
 * and preregistration QR both render through this so there's one QR
 * rendering style for the whole product.
 */
class QrCodeService
{
    public function svg(string $content, int $size = 320): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 8, null, null, Fill::uniformColor(
                new Rgb(255, 255, 255),
                new Rgb(10, 9, 12),
            )),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($content);
    }
}
