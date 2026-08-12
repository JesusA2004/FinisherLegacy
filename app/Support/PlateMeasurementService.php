<?php

namespace App\Support;

/**
 * The single place mm/pt/px conversions happen. Templates and plates always store
 * physical dimensions (mm); this is the only class allowed to turn that into pixels
 * for a given DPI or points for typography — nothing else should redo this math.
 */
class PlateMeasurementService
{
    private const MM_PER_INCH = 25.4;

    private const PT_PER_INCH = 72;

    public function mmToPx(float $mm, int $dpi): float
    {
        return $mm / self::MM_PER_INCH * $dpi;
    }

    public function pxToMm(float $px, int $dpi): float
    {
        return $px / $dpi * self::MM_PER_INCH;
    }

    public function mmToPt(float $mm): float
    {
        return $mm / self::MM_PER_INCH * self::PT_PER_INCH;
    }

    public function ptToMm(float $pt): float
    {
        return $pt / self::PT_PER_INCH * self::MM_PER_INCH;
    }

    public function mmToPixelString(float $mm, int $dpi): string
    {
        return number_format($this->mmToPx($mm, $dpi), 3, '.', '');
    }
}
