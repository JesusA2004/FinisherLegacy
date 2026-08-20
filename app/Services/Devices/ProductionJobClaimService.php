<?php

namespace App\Services\Devices;

use App\Enums\ProductionJobStatus;
use App\Exceptions\Devices\NoProductionJobAvailableException;
use App\Exceptions\Devices\ProductionJobAlreadyClaimedException;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;
use App\Services\Production\PlateProductionCoordinator;
use App\Services\Production\ProductionArtifactService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The only place a ProductionJob is atomically assigned to a device — the
 * same `lockForUpdate()` pattern App\Services\ClaimLegacyCodeService already
 * uses for claiming a Legacy Code, applied here to job claiming. Locks a
 * single row identified by its primary key (never a broad
 * WHERE-then-lock-first-match query), so the result never depends on how a
 * database engine happens to re-evaluate a filter after a blocked lock is
 * granted — see docs/adr/0002 for why that distinction matters.
 *
 * Since Slice 2 (docs/adr/0003), a successful claim also moves the job to
 * `assigned` and freezes its ProductionArtifact — see docs/adr/0003 §31,
 * the flow diagram this mirrors exactly (claim -> artifact congelado ->
 * prepare).
 */
class ProductionJobClaimService
{
    public function __construct(
        private readonly ProductionArtifactService $artifacts,
        private readonly PlateProductionCoordinator $coordinator,
    ) {}

    /**
     * Read-only — the best candidate a device could claim next, or null.
     * Never assigns anything; GET /production/jobs/next is a peek.
     */
    public function next(ProductionDevice $device): ?ProductionJob
    {
        return $this->availableJobsQuery($device)
            ->orderByDesc('priority')
            ->orderBy('queued_at')
            ->first();
    }

    /**
     * @throws NoProductionJobAvailableException if the job no longer exists
     * @throws ProductionJobAlreadyClaimedException if another device holds a live lease on it
     */
    public function claim(ProductionJob $job, ProductionDevice $device): ProductionJob
    {
        return DB::transaction(function () use ($job, $device) {
            /** @var ProductionJob|null $locked */
            $locked = ProductionJob::query()->whereKey($job->id)->lockForUpdate()->first();

            if ($locked === null) {
                throw new NoProductionJobAvailableException;
            }

            // Idempotent for the device that already holds a still-live
            // claim on it (Assigned/Preparing) — a retried claim call
            // (dropped connection, desktop retry) just refreshes the lease
            // instead of erroring or re-generating anything.
            $alreadyMine = $locked->production_device_id === $device->id
                && in_array($locked->status, [ProductionJobStatus::Assigned, ProductionJobStatus::Preparing], true);

            if (! $alreadyMine) {
                // Otherwise claimable only if genuinely unclaimed (queued),
                // or safely reclaimable because a previous claim's lease
                // lapsed before any physical work started — see
                // ProductionJob::isSafeToRelease()/hasClaimableLease() and
                // docs/adr/0003-production-state-machine.md §Lease.
                $claimable = $locked->status === ProductionJobStatus::Queued
                    || ($locked->isSafeToRelease() && $locked->hasClaimableLease());

                if (! $claimable) {
                    throw new ProductionJobAlreadyClaimedException;
                }

                $locked->update([
                    'production_device_id' => $device->id,
                    'claimed_at' => now(),
                    'lease_expires_at' => now()->addSeconds((int) config('finisher.device_lease_seconds', 900)),
                    'status' => ProductionJobStatus::Assigned,
                ]);
                $locked->refresh();
                $this->coordinator->sync($locked);

                activity()
                    ->causedBy($device)
                    ->performedOn($locked)
                    ->withProperties(['production_device_id' => $device->id])
                    ->log('Trabajo de producción reclamado por una estación.');
            } else {
                $locked->update(['lease_expires_at' => now()->addSeconds((int) config('finisher.device_lease_seconds', 900))]);
                $locked->refresh();
            }

            $this->artifacts->ensureGenerated($locked);

            return $locked->fresh();
        });
    }

    /**
     * @return Builder<ProductionJob>
     */
    private function availableJobsQuery(ProductionDevice $device): Builder
    {
        return ProductionJob::query()
            ->where('status', ProductionJobStatus::Queued)
            ->where(fn (Builder $q) => $q->whereNull('production_device_id')->orWhere('lease_expires_at', '<=', now()))
            ->when($device->event_edition_id, fn (Builder $q, int $editionId) => $q->where('event_edition_id', $editionId))
            ->when($device->machine_profile_id, fn (Builder $q, int $profileId) => $q->where(function (Builder $q) use ($profileId) {
                // A job with no machine_profile_id is generic/compatible
                // with any station — see docs/adr/0003 §59.
                $q->whereNull('machine_profile_id')->orWhere('machine_profile_id', $profileId);
            }));
    }
}
