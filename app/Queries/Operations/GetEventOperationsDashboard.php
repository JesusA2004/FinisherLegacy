<?php

namespace App\Queries\Operations;

use App\Enums\AthleteIdentityConflictStatus;
use App\Enums\ProductionJobStatus;
use App\Models\AthleteIdentityConflict;
use App\Models\EventEdition;
use App\Models\EventResult;
use App\Models\ExternalEventMapping;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;
use App\Queries\Production\GetProductionMetrics;
use App\Services\Production\EventProductionReadiness;

/**
 * One ViewModel for the whole Event Ops dashboard — App\Http\Controllers\OperatorController
 * builds zero arrays of its own, it just renders what this returns
 * (docs/adr/0006-event-operations.md §2). Every section below is a small,
 * separately-testable piece; this class only assembles them.
 */
class GetEventOperationsDashboard
{
    public function __construct(
        private readonly EventProductionReadiness $readiness,
        private readonly GetProductionMetrics $metrics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(EventEdition $edition): array
    {
        return [
            'provider' => $this->providerStatus($edition),
            'data' => $this->dataStatus($edition),
            'production' => $this->productionStatus($edition),
            'stations' => $this->stationStatus($edition),
            'readiness' => $this->readiness->check($edition),
            'metrics' => $this->metrics->handle($edition),
        ];
    }

    /**
     * @return array{connected: bool, provider_name: ?string, last_sync_at: ?string, stale: bool}
     */
    private function providerStatus(EventEdition $edition): array
    {
        $mapping = ExternalEventMapping::query()
            ->where('event_edition_id', $edition->id)
            ->with('providerConnection')
            ->latest('updated_at')
            ->first();

        if ($mapping === null || $mapping->providerConnection === null) {
            return ['connected' => false, 'provider_name' => null, 'last_sync_at' => null, 'stale' => false];
        }

        $connection = $mapping->providerConnection;
        $lastSync = $connection->last_successful_sync_at;
        $staleSeconds = (int) config('finisher.event_ops_sync_stale_seconds', 300);

        return [
            'connected' => $connection->status->value === 'connected',
            'provider_name' => $connection->name,
            'last_sync_at' => $lastSync?->diffForHumans(),
            'stale' => $lastSync === null || $lastSync->diffInSeconds(now()) > $staleSeconds,
        ];
    }

    /**
     * @return array{participants: int, results: int, conflicts: int}
     */
    private function dataStatus(EventEdition $edition): array
    {
        return [
            'participants' => $edition->participants()->count(),
            'results' => EventResult::whereHas('eventParticipant', fn ($q) => $q->where('event_edition_id', $edition->id))->count(),
            'conflicts' => AthleteIdentityConflict::query()
                ->where('status', AthleteIdentityConflictStatus::Pending)
                ->whereHas('eventParticipant', fn ($q) => $q->where('event_edition_id', $edition->id))
                ->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function productionStatus(EventEdition $edition): array
    {
        $counts = ProductionJob::query()
            ->where('event_edition_id', $edition->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $bucket = fn (array $statuses) => collect($statuses)
            ->sum(fn (ProductionJobStatus $s) => (int) ($counts[$s->value] ?? 0));

        return [
            'pending' => $bucket([ProductionJobStatus::Queued]),
            'assigned' => $bucket([ProductionJobStatus::Assigned]),
            'engraving' => $bucket([ProductionJobStatus::Preparing, ProductionJobStatus::EngravingFront, ProductionJobStatus::AwaitingFlip, ProductionJobStatus::EngravingBack, ProductionJobStatus::VerifyingQr]),
            'ready' => $bucket([ProductionJobStatus::Ready]),
            'delivered' => $bucket([ProductionJobStatus::Delivered]),
            'failed' => $bucket([ProductionJobStatus::Failed, ProductionJobStatus::Cancelled]),
        ];
    }

    /**
     * @return list<array{id: int, name: string, online: bool, current_job: ?string}>
     */
    private function stationStatus(EventEdition $edition): array
    {
        $devices = ProductionDevice::query()
            ->where('event_edition_id', $edition->id)
            ->with(['claimedJobs' => fn ($q) => $q->whereNotIn('status', [ProductionJobStatus::Delivered, ProductionJobStatus::Failed, ProductionJobStatus::Cancelled])->latest()->limit(1)])
            ->get();

        $result = [];

        foreach ($devices as $device) {
            $result[] = [
                'id' => $device->id,
                'name' => $device->name,
                'online' => $device->isOnline(),
                'current_job' => $device->claimedJobs->first()?->status->value,
            ];
        }

        return $result;
    }
}
