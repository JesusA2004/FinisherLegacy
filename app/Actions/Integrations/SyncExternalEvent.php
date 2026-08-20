<?php

namespace App\Actions\Integrations;

use App\Enums\ExternalSyncStatus;
use App\Enums\ExternalSyncType;
use App\Exceptions\Integrations\ProviderRateLimitedException;
use App\Exceptions\Integrations\ProviderUnavailableException;
use App\Models\ExternalEventMapping;
use App\Models\ExternalSyncRun;
use App\Models\ProviderConnection;
use App\Services\Integrations\EventProviderRegistry;
use Illuminate\Support\Str;
use Throwable;

/**
 * The umbrella orchestrator: opens an ExternalSyncRun, fetches from the
 * adapter, delegates to SyncExternalParticipants/SyncExternalResults,
 * persists the incremental cursor on the mapping, and closes the run. See
 * docs/adr/0005-unified-event-ingestion.md §Sync action.
 *
 * A provider-level failure (auth, 429 exhausted, unreachable) fails the
 * whole run; a row-level failure never does — it only ever shows up as an
 * ExternalSyncError and a `Partial` status (§37).
 */
class SyncExternalEvent
{
    public function __construct(
        private readonly EventProviderRegistry $registry,
        private readonly SyncExternalParticipants $syncParticipants,
        private readonly SyncExternalResults $syncResults,
    ) {}

    public function handle(ExternalEventMapping $mapping, ExternalSyncType $type, bool $fullSync = false): ExternalSyncRun
    {
        // Fetched fresh rather than via the `providerConnection` relation —
        // a caller driving several syncs off one long-lived $mapping
        // instance (e.g. a live-event loop) would otherwise see a stale
        // cached connection and miss settings updated between calls
        // (docs/adr/0005 §Mock live demo).
        $connection = ProviderConnection::findOrFail($mapping->provider_connection_id);
        $adapter = $this->registry->get($connection->provider_key);
        $chunkSize = (int) ($connection->settingsArray()['chunk_size'] ?? 250);
        $startCursor = $fullSync ? null : $mapping->cursor_after;

        $run = ExternalSyncRun::create([
            'uuid' => (string) Str::uuid(),
            'provider_connection_id' => $connection->id,
            'event_edition_id' => $mapping->event_edition_id,
            'sync_type' => $type,
            'status' => ExternalSyncStatus::Running,
            'started_at' => now(),
            'cursor_before' => $startCursor,
        ]);

        try {
            $eventData = $adapter->fetchEvent($connection, $mapping->external_event_id);
            $run->increment('events_received');

            $cursorAfter = $startCursor;

            if (in_array($type, [ExternalSyncType::Roster, ExternalSyncType::Full], true)) {
                $cursorAfter = $this->syncParticipants->handle($adapter, $run, $mapping, $connection, $eventData, $chunkSize, $startCursor);
            }

            if (in_array($type, [ExternalSyncType::Results, ExternalSyncType::Full], true)) {
                $resultsCursor = $this->syncResults->handle($adapter, $run, $mapping, $connection, $chunkSize, $startCursor);
                $cursorAfter = $type === ExternalSyncType::Full ? $cursorAfter : $resultsCursor;
            }

            $mapping->update(['cursor_before' => $startCursor, 'cursor_after' => $cursorAfter]);

            $run->refresh();
            $this->closeRun($run, $connection);
        } catch (ProviderRateLimitedException|ProviderUnavailableException $e) {
            $this->failRun($run, 'PROVIDER_UNAVAILABLE', $e->getMessage());
        } catch (Throwable $e) {
            $this->failRun($run, 'UNEXPECTED_ERROR', $e->getMessage());
        }

        return $run->fresh('errors');
    }

    private function closeRun(ExternalSyncRun $run, ProviderConnection $connection): void
    {
        $produced = $run->participants_created + $run->participants_updated
            + $run->results_created + $run->results_updated;

        $status = match (true) {
            $run->errors_count === 0 => ExternalSyncStatus::Completed,
            $produced > 0 => ExternalSyncStatus::Partial,
            default => ExternalSyncStatus::Failed,
        };

        $run->update(['status' => $status, 'completed_at' => now()]);

        if ($status !== ExternalSyncStatus::Failed) {
            $connection->update(['last_successful_sync_at' => now()]);
        }
    }

    private function failRun(ExternalSyncRun $run, string $code, string $message): void
    {
        $run->update(['status' => ExternalSyncStatus::Failed, 'completed_at' => now()]);
        $run->errors()->create(['entity_type' => 'sync', 'code' => $code, 'message' => $message]);
    }
}
