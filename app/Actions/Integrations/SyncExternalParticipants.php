<?php

namespace App\Actions\Integrations;

use App\Contracts\Integrations\EventProviderAdapter;
use App\Models\EventRace;
use App\Models\ExternalEventMapping;
use App\Models\ExternalParticipantMapping;
use App\Models\ExternalRaceMapping;
use App\Models\ExternalSyncRun;
use App\Models\ProviderConnection;
use App\Services\Integrations\EventIngestionService;
use App\Support\Integrations\ExternalEventData;
use Throwable;

/**
 * Pages through the provider's roster via App\Support\Integrations\ExternalPage
 * — never `collect()->all()` of a 50,000-row response (docs/adr/0005
 * §86-88). One participant failing never aborts the rest (§37, §108):
 * caught per-row and recorded on the ExternalSyncRun as an
 * ExternalSyncError.
 */
class SyncExternalParticipants
{
    public function __construct(private readonly EventIngestionService $ingestion) {}

    public function handle(
        EventProviderAdapter $adapter,
        ExternalSyncRun $run,
        ExternalEventMapping $mapping,
        ProviderConnection $connection,
        ExternalEventData $eventData,
        int $chunkSize,
        ?string $startCursor,
    ): ?string {
        $this->ensureRaceMappings($mapping, $eventData);

        $edition = $mapping->eventEdition;
        $cursor = $startCursor;

        do {
            $page = $adapter->fetchParticipants($connection, $mapping->external_event_id, $cursor, $chunkSize);

            foreach ($page->items as $item) {
                $run->increment('participants_received');

                $existed = ExternalParticipantMapping::query()
                    ->where('provider_connection_id', $connection->id)
                    ->where('external_participant_id', $item->externalParticipantId)
                    ->exists();

                try {
                    $race = $this->ingestion->resolveRace($item->raceExternalId, $edition, $connection);
                    $this->ingestion->ingestParticipant($item, $edition, $race, $connection, 'api');
                    $run->increment($existed ? 'participants_updated' : 'participants_created');
                } catch (Throwable $e) {
                    $run->increment('errors_count');
                    $run->errors()->create([
                        'entity_type' => 'participant',
                        'external_id' => $item->externalParticipantId,
                        'code' => 'INGEST_ERROR',
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $cursor = $page->nextCursor;
        } while ($page->hasMore);

        return $cursor;
    }

    private function ensureRaceMappings(ExternalEventMapping $mapping, ExternalEventData $eventData): void
    {
        foreach ($eventData->races as $race) {
            $exists = ExternalRaceMapping::query()
                ->where('provider_connection_id', $mapping->provider_connection_id)
                ->where('external_race_id', $race->externalId)
                ->exists();

            if ($exists) {
                continue;
            }

            $eventRace = EventRace::query()->firstOrCreate(
                ['event_edition_id' => $mapping->event_edition_id, 'name' => $race->name],
                [
                    'distance_value' => $race->distanceValue,
                    'distance_unit' => $race->distanceUnit,
                    'race_type' => $race->raceType,
                    'active' => true,
                ],
            );

            ExternalRaceMapping::create([
                'provider_connection_id' => $mapping->provider_connection_id,
                'external_event_id' => $mapping->external_event_id,
                'external_race_id' => $race->externalId,
                'event_race_id' => $eventRace->id,
            ]);
        }
    }
}
