<?php

namespace App\Support\Integrations;

final class ExternalEventData
{
    /**
     * @param  list<ExternalRaceData>  $races
     */
    public function __construct(
        public readonly string $externalId,
        public readonly string $name,
        public readonly ?int $year = null,
        public readonly ?string $date = null,
        public readonly ?string $timezone = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $country = null,
        public readonly ?string $status = null,
        public readonly array $races = [],
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
            year: isset($data['year']) ? (int) $data['year'] : null,
            date: $data['date'] ?? null,
            timezone: $data['timezone'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            country: $data['country'] ?? null,
            status: $data['status'] ?? null,
            races: array_values(array_map(fn (array $r) => ExternalRaceData::fromArray($r), $data['races'] ?? [])),
            metadata: $data['metadata'] ?? [],
        );
    }
}
