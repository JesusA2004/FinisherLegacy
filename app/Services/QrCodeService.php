<?php

namespace App\Services;

use App\Services\Qr\GdImageBackEnd;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Pure QR generation — no model coupling. LegacyCodeQrService (plates) and
 * preregistration QR both render through this so there's one QR rendering style for
 * the whole product, in both the vector (SVG) and raster (PNG, for GD-only servers
 * without ext-imagick) forms.
 */
class QrCodeService
{
    public function svg(string $content, int $size = 320): string
    {
        $renderer = new ImageRenderer($this->style($size), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($content);
    }

    public function png(string $content, int $size = 320): string
    {
        $renderer = new ImageRenderer($this->style($size), new GdImageBackEnd);

        return (new Writer($renderer))->writeString($content);
    }

    private function style(int $size): RendererStyle
    {
        return new RendererStyle($size, 8, null, null, Fill::uniformColor(
            new Rgb(255, 255, 255),
            new Rgb(10, 9, 12),
        ));
    }
}
