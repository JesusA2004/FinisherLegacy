<?php

namespace App\Jobs;

use App\Actions\Integrations\SyncExternalEvent;
use App\Enums\ExternalSyncType;
use App\Models\ExternalEventMapping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

/**
 * The only place a real HTTP call to a provider happens from a web
 * request's perspective — "Sincronizar ahora" in the admin UI dispatches
 * this instead of calling the adapter inline (docs/adr/0005 §39, never a
 * long external HTTP call inside the request/response cycle).
 * `WithoutOverlapping` keyed by mapping id stops a scheduled live-sync tick
 * from stacking on top of a still-running manual sync (§46).
 */
class SyncExternalEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public readonly int $eventMappingId,
        public readonly ExternalSyncType $syncType = ExternalSyncType::Full,
        public readonly bool $fullSync = false,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping("external-sync-mapping-{$this->eventMappingId}")];
    }

    public function handle(SyncExternalEvent $action): void
    {
        $mapping = ExternalEventMapping::findOrFail($this->eventMappingId);

        $action->handle($mapping, $this->syncType, $this->fullSync);
    }
}
