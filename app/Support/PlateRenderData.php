<?php

namespace App\Support;

use App\Models\Plate;

/**
 * Flat map of {{field}} => value, resolved once from a real Plate (or demo data) and
 * consumed identically by the web preview, the exporters, and the calibration/test
 * print — so "what we see" and "what we engrave" never come from separate calculations.
 */
class PlateRenderData
{
    /**
     * @param  array<string, string|null>  $values
     */
    private function __construct(
        public readonly array $values,
        public readonly bool $isDemo,
    ) {}

    public static function fromPlate(Plate $plate): self
    {
        $plate->loadMissing('legacyCode');
        $dynamic = $plate->dynamic_fields ?? [];

        return new self([
            'athlete_name' => $plate->athlete_name,
            'bib_number' => $plate->bib_number,
            'event_name' => $plate->event_name,
            'event_date' => $plate->event_date?->translatedFormat('d \d\e F \d\e Y'),
            'race_name' => $plate->race_name,
            'distance' => $dynamic['distance'] ?? null,
            'official_time' => $plate->official_time,
            'pace' => $plate->pace,
            'overall_position' => self::stringOrNull($dynamic['overall_position'] ?? null),
            'category' => $dynamic['category'] ?? null,
            'category_position' => self::stringOrNull($dynamic['category_position'] ?? null),
            'swim_time' => $dynamic['swim_time'] ?? null,
            'bike_time' => $dynamic['bike_time'] ?? null,
            'run_time' => $dynamic['run_time'] ?? null,
            'personal_phrase' => $dynamic['personal_phrase'] ?? null,
            'legacy_code' => $plate->legacyCode?->code,
            'plate_serial' => $plate->serial_number,
        ], false);
    }

    /**
     * Fixed calibration/demo athlete used by the editor preview and the "test de
     * grabado" print — never persisted, always visibly a placeholder.
     */
    public static function demo(): self
    {
        return new self([
            'athlete_name' => 'Zuriel Ávila',
            'bib_number' => '142',
            'event_name' => 'IRONMAN 70.3 COZUMEL',
            'event_date' => '22 de mayo de 2026',
            'race_name' => '70.3',
            'distance' => '70.3 mi',
            'official_time' => '05:21:18',
            'pace' => '4:33/km',
            'overall_position' => '142',
            'category' => '25-29',
            'category_position' => '18',
            'swim_time' => '42:15',
            'bike_time' => '2:49:33',
            'run_time' => '1:44:51',
            'personal_phrase' => 'El dolor es temporal, la gloria es para siempre.',
            'legacy_code' => 'FL-DEMOQR1',
            'plate_serial' => 'FL-2026-DEMO',
        ], true);
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function fromArray(array $values, bool $isDemo = false): self
    {
        return new self($values, $isDemo);
    }

    public function get(string $field): ?string
    {
        return $this->values[$field] ?? null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
