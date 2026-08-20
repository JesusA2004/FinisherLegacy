<?php

namespace App\Actions\Integrations;

use App\Contracts\Integrations\EventProviderAdapter;
use App\Exceptions\Integrations\ParticipantNotFoundException;
use App\Models\ExternalEventMapping;
use App\Models\ExternalSyncRun;
use App\Models\ProviderConnection;
use App\Services\Integrations\EventIngestionService;
use Throwable;

/**
 * Mirrors SyncExternalParticipants for results — a result that arrives
 * before its participant is never lost, just recorded as a retryable
 * PARTICIPANT_NOT_FOUND error; the next sync (incremental or full) tries
 * it again (docs/adr/0005 §77-78).
 */
class SyncExternalResults
{
    public function __construct(private readonly EventIngestionService $ingestion) {}

    public function handle(
        EventProviderAdapter $adapter,
        ExternalSyncRun $run,
        ExternalEventMapping $mapping,
        ProviderConnection $connection,
        int $chunkSize,
        ?string $startCursor,
    ): ?string {
        $edition = $mapping->eventEdition;
        $cursor = $startCursor;

        do {
            $page = $adapter->fetchResults($connection, $mapping->external_event_id, $cursor, $chunkSize);

            foreach ($page->items as $item) {
                $run->increment('results_received');
                $run->increment('splits_received', count($item->splits));

                try {
                    $result = $this->ingestion->ingestResult($item, $edition, $connection, $connection->provider_key);
                    $run->increment($result->wasRecentlyCreated ? 'results_created' : 'results_updated');
                } catch (ParticipantNotFoundException $e) {
                    $run->increment('errors_count');
                    $run->errors()->create([
                        'entity_type' => 'result',
                        'external_id' => $item->externalParticipantId,
                        'code' => 'PARTICIPANT_NOT_FOUND',
                        'message' => $e->getMessage(),
                    ]);
                } catch (Throwable $e) {
                    $run->increment('errors_count');
                    $run->errors()->create([
                        'entity_type' => 'result',
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
}
