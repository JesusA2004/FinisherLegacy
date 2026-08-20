<?php

namespace App\Services\Integrations;

use App\Actions\Athletes\IngestEventParticipant;
use App\Actions\Integrations\IngestEventResult;
use App\Enums\ParticipantSource;
use App\Enums\RegistrationStatus;
use App\Enums\VerificationStatus;
use App\Exceptions\Integrations\ParticipantNotFoundException;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\EventRace;
use App\Models\EventResult;
use App\Models\ExternalParticipantMapping;
use App\Models\ExternalRaceMapping;
use App\Models\ProviderConnection;
use App\Support\Integrations\ExternalParticipantData;
use App\Support\Integrations\ExternalResultData;

/**
 * The orchestrator every ingestion entry point (API sync, CSV/XLSX import)
 * calls instead of writing its own upsert logic — thin by design, it
 * delegates the actual identity/persistence work to
 * App\Actions\Athletes\IngestEventParticipant (Slice 3, reused unchanged)
 * and App\Actions\Integrations\IngestEventResult. See
 * docs/adr/0005-unified-event-ingestion.md §Application module.
 */
class EventIngestionService
{
    public function __construct(
        private readonly IngestEventParticipant $ingestParticipant,
        private readonly IngestEventResult $ingestResult,
    ) {}

    /**
     * `$race` is always resolved by the caller — App\Actions\Integrations\SyncExternalParticipants
     * via `resolveRace()` below (provider adapters split rosters by
     * external race id), the CSV importer via its existing race-name
     * lookup (docs/adr/0005 §53-55: CSV keeps its own race resolution,
     * it just feeds the same DTO + ingestion pipeline everything else
     * uses). `$connection` is null for CSV/manual sources — no
     * ExternalParticipantMapping row is written for those, since they have
     * no external participant id to key it on.
     *
     * @param  string  $sourceType  'api'|'import'
     */
    public function ingestParticipant(
        ExternalParticipantData $data,
        EventEdition $edition,
        EventRace $race,
        ?ProviderConnection $connection,
        string $sourceType,
    ): EventParticipant {
        $participant = $this->ingestParticipant->handle([
            'event_edition_id' => $edition->id,
            'event_race_id' => $race->id,
            'bib_number' => $data->bibNumber,
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'full_name' => $data->fullName,
            'email' => $data->email,
            'phone' => $data->phone,
            'gender' => $data->gender,
            'birth_date' => $data->birthDate,
            'category' => $data->category,
            'external_participant_id' => $data->externalParticipantId,
            'registration_status' => RegistrationStatus::Registered,
            'source' => $sourceType === 'import' ? ParticipantSource::Csv : ParticipantSource::Api,
            'source_reference' => $connection !== null ? (string) $connection->id : null,
            'verification_status' => VerificationStatus::Unverified,
            'external_identity_provider' => $connection?->provider_key,
            'external_identity_connection_id' => $connection !== null ? (string) $connection->id : null,
            'external_identity_subject_id' => $data->externalAthleteId,
        ], $sourceType, $connection !== null ? (string) $connection->id : null);

        if ($connection !== null && $data->externalParticipantId !== null) {
            ExternalParticipantMapping::updateOrCreate(
                ['provider_connection_id' => $connection->id, 'external_participant_id' => $data->externalParticipantId],
                ['external_athlete_id' => $data->externalAthleteId, 'event_participant_id' => $participant->id],
            );
        }

        return $participant;
    }

    /**
     * Falls back to a shared "General" race rather than throwing, so a
     * provider that doesn't split by race (or a race not mapped yet) never
     * blocks roster ingestion — see docs/adr/0005 §Race mapping. Only
     * used by the API sync path; CSV resolves its own race directly.
     */
    public function resolveRace(?string $raceExternalId, EventEdition $edition, ProviderConnection $connection): EventRace
    {
        if ($raceExternalId !== null) {
            $mapping = ExternalRaceMapping::query()
                ->where('provider_connection_id', $connection->id)
                ->where('external_race_id', $raceExternalId)
                ->first();

            if ($mapping !== null) {
                return $mapping->eventRace;
            }
        }

        return EventRace::query()->firstOrCreate(
            ['event_edition_id' => $edition->id, 'name' => 'General'],
            ['active' => true],
        );
    }

    /**
     * @throws ParticipantNotFoundException when neither the external mapping nor the
     *                                      event+bib fallback resolves a participant — the
     *                                      caller records this as a retryable sync error
     *                                      instead of losing the result (docs/adr/0005 §77).
     */
    public function ingestResult(
        ExternalResultData $data,
        EventEdition $edition,
        ProviderConnection $connection,
        string $resultSource,
    ): EventResult {
        $participant = $this->resolveParticipantForResult($data, $edition, $connection);

        return $this->ingestResult->handle($participant, $data, $resultSource);
    }

    private function resolveParticipantForResult(ExternalResultData $data, EventEdition $edition, ProviderConnection $connection): EventParticipant
    {
        $mapping = ExternalParticipantMapping::query()
            ->where('provider_connection_id', $connection->id)
            ->where('external_participant_id', $data->externalParticipantId)
            ->first();

        if ($mapping !== null) {
            return $mapping->eventParticipant;
        }

        if ($data->bibNumber !== null) {
            $participant = EventParticipant::query()
                ->where('event_edition_id', $edition->id)
                ->where('bib_number', $data->bibNumber)
                ->first();

            if ($participant !== null) {
                return $participant;
            }
        }

        throw new ParticipantNotFoundException(
            "Sin participante para external_participant_id={$data->externalParticipantId} en la edición #{$edition->id}.",
        );
    }
}
