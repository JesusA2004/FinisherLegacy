<?php

namespace App\Support;

/**
 * Curated catalog of safe vector icons for the back face's thematic
 * iconography (§17 of the two-face production rules). Every shape is
 * plain data — polylines and circles normalized to a 0..1 unit square —
 * never arbitrary SVG/path strings from user input, so a template can only
 * ever reference one of these known IDs. No emoji: emoji are font glyphs
 * that can render differently per system/printer, unacceptable for a laser
 * production file (see docs/plate-production.md §2).
 */
class PlateVectorIcons
{
    /**
     * @return array<string, list<array{type: 'polyline', points: list<array{0: float, 1: float}>}|array{type: 'circle', cx: float, cy: float, r: float}>>
     */
    public static function catalog(): array
    {
        return [
            'running' => [
                ['type' => 'circle', 'cx' => 0.47, 'cy' => 0.12, 'r' => 0.07],
                ['type' => 'polyline', 'points' => [[0.47, 0.19], [0.55, 0.38]]],
                ['type' => 'polyline', 'points' => [[0.55, 0.38], [0.36, 0.55], [0.24, 0.5]]],
                ['type' => 'polyline', 'points' => [[0.55, 0.38], [0.72, 0.55], [0.86, 0.5]]],
                ['type' => 'polyline', 'points' => [[0.5, 0.24], [0.3, 0.18]]],
                ['type' => 'polyline', 'points' => [[0.5, 0.24], [0.66, 0.4]]],
            ],
            'cycling' => [
                ['type' => 'circle', 'cx' => 0.24, 'cy' => 0.72, 'r' => 0.16],
                ['type' => 'circle', 'cx' => 0.76, 'cy' => 0.72, 'r' => 0.16],
                ['type' => 'polyline', 'points' => [[0.24, 0.72], [0.46, 0.42], [0.66, 0.42], [0.76, 0.72]]],
                ['type' => 'polyline', 'points' => [[0.46, 0.42], [0.4, 0.26]]],
                ['type' => 'polyline', 'points' => [[0.66, 0.42], [0.76, 0.32]]],
                ['type' => 'polyline', 'points' => [[0.34, 0.72], [0.5, 0.72]]],
            ],
            'swimming' => [
                ['type' => 'circle', 'cx' => 0.28, 'cy' => 0.34, 'r' => 0.055],
                ['type' => 'polyline', 'points' => [[0.33, 0.38], [0.55, 0.42]]],
                ['type' => 'polyline', 'points' => [[0.55, 0.42], [0.72, 0.26]]],
                ['type' => 'polyline', 'points' => [[0.08, 0.62], [0.28, 0.52], [0.46, 0.64], [0.64, 0.52], [0.92, 0.62]]],
            ],
            'mountain' => [
                ['type' => 'polyline', 'points' => [[0.08, 0.78], [0.36, 0.28], [0.52, 0.52], [0.62, 0.38], [0.92, 0.78]]],
                ['type' => 'polyline', 'points' => [[0.3, 0.44], [0.36, 0.28], [0.42, 0.44]]],
            ],
            'finish' => [
                ['type' => 'polyline', 'points' => [[0.3, 0.14], [0.3, 0.86]]],
                ['type' => 'polyline', 'points' => [[0.3, 0.14], [0.62, 0.24], [0.3, 0.34]]],
                ['type' => 'polyline', 'points' => [[0.18, 0.86], [0.42, 0.86]]],
            ],
            'trophy' => [
                ['type' => 'polyline', 'points' => [[0.3, 0.18], [0.3, 0.4], [0.4, 0.56], [0.6, 0.56], [0.7, 0.4], [0.7, 0.18], [0.3, 0.18]]],
                ['type' => 'polyline', 'points' => [[0.3, 0.24], [0.18, 0.24], [0.18, 0.4], [0.3, 0.4]]],
                ['type' => 'polyline', 'points' => [[0.7, 0.24], [0.82, 0.24], [0.82, 0.4], [0.7, 0.4]]],
                ['type' => 'polyline', 'points' => [[0.5, 0.56], [0.5, 0.7]]],
                ['type' => 'polyline', 'points' => [[0.34, 0.7], [0.66, 0.7], [0.66, 0.8], [0.34, 0.8], [0.34, 0.7]]],
            ],
        ];
    }

    /** @return list<string> */
    public static function ids(): array
    {
        return array_keys(self::catalog());
    }

    public static function exists(string $id): bool
    {
        return array_key_exists($id, self::catalog());
    }

    /**
     * @return list<array{type: 'polyline', points: list<array{0: float, 1: float}>}|array{type: 'circle', cx: float, cy: float, r: float}>
     */
    public static function shapesFor(string $id): array
    {
        return self::catalog()[$id] ?? [];
    }
}
