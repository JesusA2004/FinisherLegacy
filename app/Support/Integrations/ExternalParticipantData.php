<?php

namespace App\Support\Integrations;

/**
 * A roster row, provider-agnostic — whatever the source calls "runner_number",
 * "bib", or "dorsal" arrives here already normalized to `bibNumber`. See
 * docs/adr/0005-unified-event-ingestion.md §Canonical DTOs.
 */
final class ExternalParticipantData
{
    public function __construct(
        /**
         * Null for sources with no external id of their own — CSV/XLSX
         * rows without a provider id column (docs/adr/0005 §76). When
         * null, identity for future syncs falls back to
         * `event_edition_id` + `bib_number` and no
         * ExternalParticipantMapping row is written.
         */
        public readonly ?string $externalParticipantId,
        public readonly string $bibNumber,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $fullName = null,
        public readonly ?string $externalAthleteId = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $birthDate = null,
        public readonly ?string $gender = null,
        public readonly ?string $category = null,
        public readonly ?string $raceExternalId = null,
        public readonly ?string $status = null,
        public readonly ?string $updatedAt = null,
        /** @var array<string, mixed> */
        public readonly array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $firstName = trim((string) $data['first_name']);
        $lastName = trim((string) $data['last_name']);

        return new self(
            externalParticipantId: isset($data['external_participant_id']) ? (string) $data['external_participant_id'] : null,
            bibNumber: (string) $data['bib_number'],
            firstName: $firstName,
            lastName: $lastName,
            fullName: $data['full_name'] ?? trim("{$firstName} {$lastName}"),
            externalAthleteId: isset($data['external_athlete_id']) ? (string) $data['external_athlete_id'] : null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            birthDate: $data['birth_date'] ?? null,
            gender: $data['gender'] ?? null,
            category: $data['category'] ?? null,
            raceExternalId: isset($data['race_external_id']) ? (string) $data['race_external_id'] : null,
            status: $data['status'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
