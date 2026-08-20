<?php

namespace App\Actions\Devices;

use App\Actions\Production\ProductionJobAction;
use App\Enums\ProductionJobStatus;
use App\Exceptions\Production\InvalidProductionTransitionException;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;
use Illuminate\Support\Facades\DB;

/**
 * Voluntarily releases a job's claim before its lease expires (e.g. the
 * desktop detects it can't service this job after all) — puts it straight
 * back in `queued` so any device's `next()` can pick it up again. Only
 * safe from `assigned`/`preparing` (ProductionJob::isSafeToRelease()); the
 * state machine has no `queued` target for any engraving state, so this
 * fails with INVALID_PRODUCTION_TRANSITION on its own once physical work
 * has started — see docs/adr/0003-production-state-machine.md §Lease.
 *
 * Device-only by nature (a web operator never "claims" a job, so there is
 * nothing for manual mode to release) — kept under Actions/Devices rather
 * than the shared Actions/Production, unlike every other workflow step.
 */
class ReleaseProductionJob extends ProductionJobAction
{
    public function handle(ProductionJob $job, ProductionDevice $device): ProductionJob
    {
        return DB::transaction(function () use ($job, $device) {
            /** @var ProductionJob $locked */
            $locked = ProductionJob::query()->whereKey($job->id)->lockForUpdate()->first()
                ?? throw new InvalidProductionTransitionException($job->status->value, ProductionJobStatus::Queued->value);

            $this->assertOwnership($locked, $device);

            return $this->transition($locked, ProductionJobStatus::Queued, [
                'production_device_id' => null,
                'claimed_at' => null,
                'lease_expires_at' => null,
            ], $device);
        });
    }
}
