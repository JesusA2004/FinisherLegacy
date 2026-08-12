<?php

namespace App\Support;

/**
 * Simple, reliable auto-fit: shrink the font size in 0.5pt steps until an estimated
 * text width fits the box, down to a floor. This is a heuristic (average glyph width
 * per em), not real font metrics — good enough to avoid obvious overflow on names,
 * documented as a known approximation rather than something that silently truncates.
 */
class TextFitService
{
    private const AVG_CHAR_WIDTH_EM = 0.56;

    private const STEP_PT = 0.5;

    public function __construct(private readonly PlateMeasurementService $measure) {}

    public function estimateWidthMm(string $text, float $fontSizePt): float
    {
        if ($text === '') {
            return 0.0;
        }

        $emMm = $this->measure->ptToMm($fontSizePt);

        return mb_strlen($text) * $emMm * self::AVG_CHAR_WIDTH_EM;
    }

    /**
     * @return array{font_size_pt: float, fits: bool}
     */
    public function fit(string $text, float $maxWidthMm, float $startPt, float $minPt): array
    {
        $pt = $startPt;

        while ($pt > $minPt) {
            if ($this->estimateWidthMm($text, $pt) <= $maxWidthMm) {
                return ['font_size_pt' => $pt, 'fits' => true];
            }

            $pt -= self::STEP_PT;
        }

        return ['font_size_pt' => $minPt, 'fits' => $this->estimateWidthMm($text, $minPt) <= $maxWidthMm];
    }
}
