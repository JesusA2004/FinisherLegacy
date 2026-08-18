<?php

namespace App\Services;

use App\Enums\PlateBackTransform;
use App\Models\PlateTemplateVersion;
use App\Support\PlateMeasurementService;
use App\Support\PlateRenderData;
use App\Support\PlateVectorIcons;
use GdImage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Rasterizes a plate face directly with GD (text, lines, rects, QR, images) instead
 * of converting the SVG — this server has no ext-imagick to rasterize SVG with. Reads
 * the exact same resolved element list PlateTemplateRenderService produces for SVG/PDF,
 * so positions, auto-fit sizes, and warnings are always computed once, not per format.
 */
class GdPlateRenderer
{
    public function __construct(
        private readonly PlateTemplateRenderService $renderer,
        private readonly PlateMeasurementService $measure,
        private readonly QrCodeService $qr,
    ) {}

    public function renderPng(
        PlateTemplateVersion $version,
        string $face,
        PlateRenderData $data,
        string $mode = PlateTemplateRenderService::MODE_PRODUCT,
        int $dpi = 300,
        bool $transparentBackground = false,
    ): string {
        $template = $version->plateTemplate;
        $widthPx = (int) round($this->measure->mmToPx((float) $template->width_mm, $dpi));
        $heightPx = (int) round($this->measure->mmToPx((float) $template->height_mm, $dpi));

        $image = imagecreatetruecolor(max(1, $widthPx), max(1, $heightPx));

        if ($image === false) {
            throw new RuntimeException('Unable to allocate plate image canvas.');
        }

        imagesavealpha($image, true);
        imagealphablending($image, true);

        $bg = $transparentBackground
            ? imagecolorallocatealpha($image, 255, 255, 255, 127)
            : imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bg === false ? 0 : $bg);

        $isProduction = $mode === PlateTemplateRenderService::MODE_PRODUCTION;
        $resolved = $this->renderer->resolveElements($version, $face, $data);

        foreach ($resolved['elements'] as $element) {
            $this->drawElement($image, $element, $dpi, $isProduction);
        }

        $backTransform = $template->back_transform ?? PlateBackTransform::None;

        if ($isProduction && $face === 'back' && $backTransform !== PlateBackTransform::None) {
            $image = $this->applyBackTransform($image, $backTransform);
        }

        ob_start();
        imagepng($image);
        $blob = (string) ob_get_clean();
        imagedestroy($image);

        return $blob;
    }

    private function applyBackTransform(GdImage $image, PlateBackTransform $transform): GdImage
    {
        $result = match ($transform) {
            PlateBackTransform::MirrorX => imageflip($image, IMG_FLIP_HORIZONTAL) ? $image : null,
            PlateBackTransform::MirrorY => imageflip($image, IMG_FLIP_VERTICAL) ? $image : null,
            PlateBackTransform::Rotate180 => imagerotate($image, 180, 0),
            PlateBackTransform::None => $image,
        };

        return $result instanceof GdImage ? $result : $image;
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function drawElement(GdImage $image, array $element, int $dpi, bool $isProduction): void
    {
        $type = $element['type'] ?? 'static_text';

        match ($type) {
            'static_text', 'dynamic_text', 'serial' => $this->drawText($image, $element, $dpi, $isProduction),
            'line' => $this->drawLine($image, $element, $dpi, $isProduction),
            'rect' => $this->drawRect($image, $element, $dpi, $isProduction),
            'qr' => $this->drawQr($image, $element, $dpi),
            'image', 'logo', 'icon' => $this->drawImage($image, $element, $dpi),
            'vector_icon' => $this->drawVectorIcon($image, $element, $dpi, $isProduction),
            default => null,
        };
    }

    /**
     * Standalone transparent raster of one vector_icon element, for embedding
     * as an <img> in the PDF renderer (dompdf has no native SVG polyline
     * support) — positioned at the origin, the caller places it via CSS.
     *
     * @param  array<string, mixed>  $element
     */
    public function renderVectorIconPng(array $element, int $widthPx, int $heightPx, bool $isProduction): string
    {
        $image = imagecreatetruecolor(max(1, $widthPx), max(1, $heightPx));

        if ($image === false) {
            throw new RuntimeException('Unable to allocate vector icon canvas.');
        }

        imagesavealpha($image, true);
        imagealphablending($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent === false ? 0 : $transparent);

        $color = $isProduction ? '#000000' : (string) ($element['stroke'] ?? '#000000');
        $gdColor = $this->allocateColor($image, $color);
        $thickness = max(1, (int) round($widthPx * 0.03));
        $this->paintVectorIconShapes($image, (string) ($element['icon'] ?? ''), 0, 0, $widthPx, $heightPx, $gdColor, $thickness);

        ob_start();
        imagepng($image);
        $blob = (string) ob_get_clean();
        imagedestroy($image);

        return $blob;
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function drawVectorIcon(GdImage $image, array $element, int $dpi, bool $isProduction): void
    {
        $x = $this->px((float) ($element['x_mm'] ?? 0), $dpi);
        $y = $this->px((float) ($element['y_mm'] ?? 0), $dpi);
        $w = $this->px((float) ($element['width_mm'] ?? 0), $dpi);
        $h = $this->px((float) ($element['height_mm'] ?? 0), $dpi);

        $color = $isProduction ? '#000000' : (string) ($element['stroke'] ?? '#000000');
        $gdColor = $this->allocateColor($image, $color);
        $thickness = max(1, $this->px((float) ($element['stroke_width_mm'] ?? 0.4), $dpi));

        $this->paintVectorIconShapes($image, (string) ($element['icon'] ?? ''), $x, $y, $w, $h, $gdColor, $thickness);
    }

    private function paintVectorIconShapes(GdImage $image, string $iconId, int $x, int $y, int $w, int $h, int $gdColor, int $thickness): void
    {
        $shapes = PlateVectorIcons::shapesFor($iconId);

        if ($shapes === []) {
            return;
        }

        imagesetthickness($image, $thickness);

        foreach ($shapes as $shape) {
            if ($shape['type'] === 'circle') {
                $cx = $x + (int) round($shape['cx'] * $w);
                $cy = $y + (int) round($shape['cy'] * $h);
                $r = (int) round($shape['r'] * min($w, $h)) * 2;
                imageellipse($image, $cx, $cy, max(1, $r), max(1, $r), $gdColor);

                continue;
            }

            $points = $shape['points'];
            for ($i = 0; $i < count($points) - 1; $i++) {
                $x1 = $x + (int) round($points[$i][0] * $w);
                $y1 = $y + (int) round($points[$i][1] * $h);
                $x2 = $x + (int) round($points[$i + 1][0] * $w);
                $y2 = $y + (int) round($points[$i + 1][1] * $h);
                imageline($image, $x1, $y1, $x2, $y2, $gdColor);
            }
        }

        imagesetthickness($image, 1);
    }

    private function px(float $mm, int $dpi): int
    {
        return (int) round($this->measure->mmToPx($mm, $dpi));
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function drawText(GdImage $image, array $element, int $dpi, bool $isProduction): void
    {
        $text = (string) ($element['resolved_text'] ?? '');

        if ($text === '') {
            return;
        }

        $fontSizePt = (float) ($element['computed_font_size_pt'] ?? $element['font_size_pt'] ?? 10);
        $fontSizePx = $this->measure->mmToPx($this->measure->ptToMm($fontSizePt), $dpi);
        $weight = (int) ($element['font_weight'] ?? 400);
        $fontPath = (string) config($weight >= 600 ? 'plate-studio.fonts.bold' : 'plate-studio.fonts.regular');

        $x = $this->px((float) ($element['x_mm'] ?? 0), $dpi);
        $y = $this->px((float) ($element['y_mm'] ?? 0), $dpi);
        $w = $this->px((float) ($element['width_mm'] ?? 0), $dpi);
        $h = $this->px((float) ($element['height_mm'] ?? 0), $dpi);

        $color = $isProduction ? '#000000' : (string) ($element['color'] ?? '#000000');
        $gdColor = $this->allocateColor($image, $color);

        $bbox = imagettfbbox($fontSizePx, 0, $fontPath, $text);

        if ($bbox === false) {
            return;
        }

        $textWidth = abs($bbox[2] - $bbox[0]);
        $textHeight = abs($bbox[1] - $bbox[7]);

        $align = $element['text_align'] ?? 'left';
        $textX = match ($align) {
            'center' => $x + max(0, ($w - $textWidth) / 2),
            'right' => $x + max(0, $w - $textWidth),
            default => $x,
        };
        $textY = $y + $h / 2 + $textHeight / 2;

        imagettftext($image, $fontSizePx, 0, (int) round($textX), (int) round($textY), $gdColor, $fontPath, $text);
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function drawLine(GdImage $image, array $element, int $dpi, bool $isProduction): void
    {
        $x = $this->px((float) ($element['x_mm'] ?? 0), $dpi);
        $y = $this->px((float) ($element['y_mm'] ?? 0), $dpi);
        $x2 = $this->px((float) ($element['x_mm'] ?? 0) + (float) ($element['width_mm'] ?? 0), $dpi);
        $y2 = $this->px((float) ($element['y_mm'] ?? 0) + (float) ($element['height_mm'] ?? 0), $dpi);

        $color = $isProduction ? '#000000' : (string) ($element['stroke'] ?? '#000000');
        $gdColor = $this->allocateColor($image, $color);

        $thickness = max(1, $this->px((float) ($element['stroke_width_mm'] ?? 0.2), $dpi));
        imagesetthickness($image, $thickness);
        imageline($image, $x, $y, $x2, $y2, $gdColor);
        imagesetthickness($image, 1);
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function drawRect(GdImage $image, array $element, int $dpi, bool $isProduction): void
    {
        $x = $this->px((float) ($element['x_mm'] ?? 0), $dpi);
        $y = $this->px((float) ($element['y_mm'] ?? 0), $dpi);
        $w = $this->px((float) ($element['width_mm'] ?? 0), $dpi);
        $h = $this->px((float) ($element['height_mm'] ?? 0), $dpi);

        $fill = $isProduction ? 'none' : (string) ($element['fill'] ?? 'none');
        if ($fill !== 'none' && $fill !== '') {
            imagefilledrectangle($image, $x, $y, $x + $w, $y + $h, $this->allocateColor($image, $fill));
        }

        $strokeColor = $isProduction ? '#000000' : (string) ($element['stroke'] ?? '#000000');
        $gdColor = $this->allocateColor($image, $strokeColor);
        $thickness = max(1, $this->px((float) ($element['stroke_width_mm'] ?? 0.2), $dpi));
        imagesetthickness($image, $thickness);
        imagerectangle($image, $x, $y, $x + $w, $y + $h, $gdColor);
        imagesetthickness($image, 1);
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function drawQr(GdImage $image, array $element, int $dpi): void
    {
        $content = $element['qr_content'] ?? null;

        if (! $content) {
            return;
        }

        $sizePx = $this->px((float) ($element['width_mm'] ?? 0), $dpi);

        if ($sizePx <= 0) {
            return;
        }

        $qrPng = $this->qr->png((string) $content, $sizePx);
        $qrImage = imagecreatefromstring($qrPng);

        if ($qrImage === false) {
            return;
        }

        $x = $this->px((float) ($element['x_mm'] ?? 0), $dpi);
        $y = $this->px((float) ($element['y_mm'] ?? 0), $dpi);
        imagecopy($image, $qrImage, $x, $y, 0, 0, imagesx($qrImage), imagesy($qrImage));
        imagedestroy($qrImage);
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function drawImage(GdImage $image, array $element, int $dpi): void
    {
        $src = $element['src'] ?? null;

        if (! $src || ! is_string($src)) {
            return;
        }

        $path = Storage::disk('public')->exists($src) ? Storage::disk('public')->path($src) : null;

        if ($path === null || ! is_file($path)) {
            return;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return;
        }

        $source = imagecreatefromstring($contents);

        if ($source === false) {
            return;
        }

        $x = $this->px((float) ($element['x_mm'] ?? 0), $dpi);
        $y = $this->px((float) ($element['y_mm'] ?? 0), $dpi);
        $w = $this->px((float) ($element['width_mm'] ?? 0), $dpi);
        $h = $this->px((float) ($element['height_mm'] ?? 0), $dpi);

        imagecopyresampled($image, $source, $x, $y, 0, 0, $w, $h, imagesx($source), imagesy($source));
        imagedestroy($source);
    }

    private function allocateColor(GdImage $image, string $hex): int
    {
        $hex = ltrim($hex, '#');
        [$r, $g, $b] = strlen($hex) === 6
            ? [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))]
            : [0, 0, 0];

        $allocated = imagecolorallocate($image, self::clampChannel((int) $r), self::clampChannel((int) $g), self::clampChannel((int) $b));

        return $allocated === false ? 0 : $allocated;
    }

    /**
     * @return int<0, 255>
     */
    private static function clampChannel(int $value): int
    {
        return max(0, min(255, $value));
    }
}
