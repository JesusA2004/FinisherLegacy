<?php

namespace App\Support\Integrations;

/**
 * One timing checkpoint on a result — 5K split, T1/T2 in a triathlon, a
 * trail checkpoint. See docs/adr/0005-unified-event-ingestion.md §Canonical DTOs.
 */
final class ExternalSplitData
{
    public function __construct(
        public readonly ?string $type,
        public readonly string $label,
        public readonly int $sequence,
        public readonly ?float $distanceValue = null,
        public readonly ?string $distanceUnit = null,
        public readonly ?string $segmentTime = null,
        public readonly ?string $elapsedTime = null,
        public readonly ?string $pace = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? null,
            label: (string) $data['label'],
            sequence: (int) ($data['sequence'] ?? 0),
            distanceValue: isset($data['distance_value']) ? (float) $data['distance_value'] : null,
            distanceUnit: $data['distance_unit'] ?? null,
            segmentTime: $data['segment_time'] ?? null,
            elapsedTime: $data['elapsed_time'] ?? null,
            pace: $data['pace'] ?? null,
        );
    }
}
