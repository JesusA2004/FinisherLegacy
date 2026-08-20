<?php

namespace App\Support\Integrations;

/**
 * A finisher-line reading for one participant, provider-agnostic. See
 * docs/adr/0005-unified-event-ingestion.md §Canonical DTOs.
 */
final class ExternalResultData
{
    /**
     * @param  list<ExternalSplitData>  $splits
     */
    public function __construct(
        public readonly string $externalParticipantId,
        public readonly ?string $externalResultId = null,
        /**
         * Fallback identity when a provider result mapping doesn't exist
         * yet — matched against `event_edition_id` + `bib_number`
         * (docs/adr/0005 §Result matching). Never matched by name.
         */
        public readonly ?string $bibNumber = null,
        public readonly ?string $officialTime = null,
        public readonly ?string $chipTime = null,
        public readonly ?string $pace = null,
        public readonly ?int $overallPosition = null,
        public readonly ?int $genderPosition = null,
        public readonly ?int $categoryPosition = null,
        public readonly ?string $status = null,
        public readonly array $splits = [],
        public readonly ?string $updatedAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            externalParticipantId: (string) $data['external_participant_id'],
            externalResultId: isset($data['external_result_id']) ? (string) $data['external_result_id'] : null,
            bibNumber: isset($data['bib_number']) ? (string) $data['bib_number'] : null,
            officialTime: $data['official_time'] ?? null,
            chipTime: $data['chip_time'] ?? null,
            pace: $data['pace'] ?? null,
            overallPosition: isset($data['overall_position']) ? (int) $data['overall_position'] : null,
            genderPosition: isset($data['gender_position']) ? (int) $data['gender_position'] : null,
            categoryPosition: isset($data['category_position']) ? (int) $data['category_position'] : null,
            status: $data['status'] ?? null,
            splits: array_values(array_map(fn (array $s) => ExternalSplitData::fromArray($s), $data['splits'] ?? [])),
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}
