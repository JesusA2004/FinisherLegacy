<?php

namespace App\Support\Integrations;

final class ExternalRaceData
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $name,
        public readonly ?float $distanceValue = null,
        public readonly ?string $distanceUnit = null,
        public readonly ?string $raceType = null,
        public readonly ?string $startTime = null,
        /** @var array<string, mixed> */
        public readonly array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            externalId: (string) $data['external_id'],
            name: (string) $data['name'],
            distanceValue: isset($data['distance_value']) ? (float) $data['distance_value'] : null,
            distanceUnit: $data['distance_unit'] ?? null,
            raceType: $data['race_type'] ?? null,
            startTime: $data['start_time'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
