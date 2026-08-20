<?php

namespace App\Services\Devices;

use App\Enums\ProductionJobStatus;
use App\Exceptions\Devices\NoProductionJobAvailableException;
use App\Exceptions\Devices\ProductionJobAlreadyClaimedException;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;
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
 * Deliberately does not change `ProductionJob.status` or `Plate.status` —
 * see the migration comment on `production_device_id`/`claimed_at`/
 * `lease_expires_at` and docs/adr/0002 §Deuda. This slice only proves who
 * holds the job, not a new production state machine.
 */
class ProductionJobClaimService
{
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

            if ($locked->status !== ProductionJobStatus::Queued) {
                throw new ProductionJobAlreadyClaimedException;
            }

            // Idempotent for the device that already holds it — a retried
            // claim call (dropped connection, desktop retry) just refreshes
            // the lease instead of erroring.
            if ($locked->production_device_id !== null
                && $locked->production_device_id !== $device->id
                && ! $locked->hasClaimableLease()) {
                throw new ProductionJobAlreadyClaimedException;
            }

            $locked->update([
                'production_device_id' => $device->id,
                'claimed_at' => now(),
                'lease_expires_at' => now()->addSeconds((int) config('finisher.device_lease_seconds', 900)),
            ]);

            activity()
                ->causedBy($device)
                ->performedOn($locked)
                ->withProperties(['production_device_id' => $device->id])
                ->log('Trabajo de producción reclamado por una estación.');

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
            ->when($device->event_edition_id, fn (Builder $q, int $editionId) => $q->where('event_edition_id', $editionId));
    }
}
