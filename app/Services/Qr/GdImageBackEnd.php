<?php

namespace App\Services\Qr;

use BaconQrCode\Renderer\Color\Alpha;
use BaconQrCode\Renderer\Color\ColorInterface;
use BaconQrCode\Renderer\Image\ImageBackEndInterface;
use BaconQrCode\Renderer\Path\Close;
use BaconQrCode\Renderer\Path\Curve;
use BaconQrCode\Renderer\Path\EllipticArc;
use BaconQrCode\Renderer\Path\Line;
use BaconQrCode\Renderer\Path\Move;
use BaconQrCode\Renderer\Path\Path;
use BaconQrCode\Renderer\RendererStyle\Gradient;
use GdImage;
use RuntimeException;

/**
 * GD-based QR image back end for environments without ext-imagick (this app's
 * production server only has ext-gd). Only straight Move/Line/Close operations are
 * rasterized — the only shapes the default SquareModule/ModuleEye style ever emits —
 * using an even-odd scanline fill so the finder-pattern "eyes" render as rings with a
 * hole, not solid squares, matching the SVG output pixel-for-pixel in structure.
 */
final class GdImageBackEnd implements ImageBackEndInterface
{
    /** @var list<array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float}> */
    private array $matrixStack = [];

    private ?GdImage $image = null;

    private int $size = 0;

    public function new(int $size, ColorInterface $backgroundColor): void
    {
        $this->size = $size;
        $image = imagecreatetruecolor(max(1, $size), max(1, $size));

        if ($image === false) {
            throw new RuntimeException('Unable to allocate QR image canvas.');
        }

        $this->image = $image;
        imagesavealpha($this->image, true);
        imagealphablending($this->image, true);
        imagefill($this->image, 0, 0, $this->allocate($backgroundColor));

        $this->matrixStack = [[1.0, 0.0, 0.0, 1.0, 0.0, 0.0]];
    }

    public function scale(float $size): void
    {
        $this->compose([$size, 0.0, 0.0, $size, 0.0, 0.0]);
    }

    public function translate(float $x, float $y): void
    {
        $this->compose([1.0, 0.0, 0.0, 1.0, $x, $y]);
    }

    public function rotate(int $degrees): void
    {
        $rad = deg2rad($degrees);
        $this->compose([cos($rad), sin($rad), -sin($rad), cos($rad), 0.0, 0.0]);
    }

    public function push(): void
    {
        $this->matrixStack[] = $this->current();
    }

    public function pop(): void
    {
        if (count($this->matrixStack) > 1) {
            array_pop($this->matrixStack);
        }
    }

    public function drawPathWithColor(Path $path, ColorInterface $color): void
    {
        $this->fillPath($path, $color);
    }

    public function drawPathWithGradient(Path $path, Gradient $gradient, float $x, float $y, float $width, float $height): void
    {
        // Gradients never reach this back end in practice (LegacyCodeQrService only
        // asks for uniform-color QR codes); fall back to the gradient's start color
        // so output stays sane if this is ever exercised.
        $this->fillPath($path, $gradient->getStartColor());
    }

    public function done(): string
    {
        if ($this->image === null) {
            throw new RuntimeException('No image has been started');
        }

        ob_start();
        imagepng($this->image);
        $blob = (string) ob_get_clean();
        imagedestroy($this->image);
        $this->image = null;

        return $blob;
    }

    /**
     * @param  array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float}  $local
     */
    private function compose(array $local): void
    {
        $current = array_pop($this->matrixStack) ?? [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];
        [$a, $b, $c, $d, $e, $f] = $current;
        [$la, $lb, $lc, $ld, $le, $lf] = $local;

        $this->matrixStack[] = [
            $a * $la + $c * $lb,
            $b * $la + $d * $lb,
            $a * $lc + $c * $ld,
            $b * $lc + $d * $ld,
            $a * $le + $c * $lf + $e,
            $b * $le + $d * $lf + $f,
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float}
     */
    private function current(): array
    {
        $key = array_key_last($this->matrixStack);

        return $key === null ? [1.0, 0.0, 0.0, 1.0, 0.0, 0.0] : $this->matrixStack[$key];
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function apply(float $x, float $y): array
    {
        [$a, $b, $c, $d, $e, $f] = $this->current();

        return [$a * $x + $c * $y + $e, $b * $x + $d * $y + $f];
    }

    private function fillPath(Path $path, ColorInterface $color): void
    {
        if ($this->image === null) {
            throw new RuntimeException('No image has been started');
        }

        $gdColor = $this->allocate($color);

        /** @var list<list<array{0: float, 1: float}>> $subpaths */
        $subpaths = [];
        /** @var list<array{0: float, 1: float}> $current */
        $current = [];

        foreach ($path as $operation) {
            if ($operation instanceof Move) {
                if (count($current) > 1) {
                    $subpaths[] = $current;
                }
                $current = [$this->apply($operation->getX(), $operation->getY())];
            } elseif ($operation instanceof Line) {
                $current[] = $this->apply($operation->getX(), $operation->getY());
            } elseif ($operation instanceof Curve) {
                $current[] = $this->apply($operation->getX3(), $operation->getY3());
            } elseif ($operation instanceof EllipticArc) {
                $current[] = $this->apply($operation->getX(), $operation->getY());
            } elseif ($operation instanceof Close) {
                if (count($current) > 1) {
                    $subpaths[] = $current;
                }
                $current = [];
            }
        }

        if (count($current) > 1) {
            $subpaths[] = $current;
        }

        $this->scanlineFillEvenOdd($subpaths, $gdColor);
    }

    /**
     * Standard even-odd scanline fill across every subpath at once, so an inner
     * subpath (a hole, e.g. a QR eye's white center) is punched out correctly
     * regardless of its winding direction.
     *
     * @param  list<list<array{0: float, 1: float}>>  $subpaths
     */
    private function scanlineFillEvenOdd(array $subpaths, int $gdColor): void
    {
        if (! $subpaths || $this->image === null) {
            return;
        }

        $minY = PHP_INT_MAX;
        $maxY = PHP_INT_MIN;

        foreach ($subpaths as $points) {
            foreach ($points as $point) {
                $minY = min($minY, (int) floor($point[1]));
                $maxY = max($maxY, (int) ceil($point[1]));
            }
        }

        $minY = max(0, $minY);
        $maxY = min($this->size - 1, $maxY);

        for ($y = $minY; $y <= $maxY; $y++) {
            $scanY = $y + 0.5;
            $intersections = [];

            foreach ($subpaths as $points) {
                $count = count($points);

                for ($i = 0; $i < $count; $i++) {
                    [$x1, $y1] = $points[$i];
                    [$x2, $y2] = $points[($i + 1) % $count];

                    if ($y1 === $y2) {
                        continue;
                    }

                    if ($scanY >= min($y1, $y2) && $scanY < max($y1, $y2)) {
                        $t = ($scanY - $y1) / ($y2 - $y1);
                        $intersections[] = $x1 + $t * ($x2 - $x1);
                    }
                }
            }

            sort($intersections);
            $count = count($intersections);

            for ($i = 0; $i + 1 < $count; $i += 2) {
                $xStart = (int) round($intersections[$i]);
                $xEnd = (int) round($intersections[$i + 1]) - 1;

                if ($xEnd >= $xStart) {
                    imageline($this->image, max(0, $xStart), $y, min($this->size - 1, $xEnd), $y, $gdColor);
                }
            }
        }
    }

    private function allocate(ColorInterface $color): int
    {
        if ($this->image === null) {
            throw new RuntimeException('No image has been started');
        }

        $alpha = 0;
        $base = $color;

        if ($color instanceof Alpha) {
            $alpha = max(0, min(127, (int) round((100 - $color->getAlpha()) / 100 * 127)));
            $base = $color->getBaseColor();
        }

        $rgb = $base->toRgb();

        $allocated = imagecolorallocatealpha(
            $this->image,
            self::clampChannel($rgb->getRed()),
            self::clampChannel($rgb->getGreen()),
            self::clampChannel($rgb->getBlue()),
            $alpha,
        );

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
