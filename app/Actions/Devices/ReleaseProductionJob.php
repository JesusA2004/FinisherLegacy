<?php

namespace App\Actions\Devices;

use App\Models\ProductionDevice;
use App\Models\ProductionJob;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Voluntarily releases a job's claim before its lease expires (e.g. the
 * desktop detects it can't service this job after all). Not exposed over
 * HTTP in Slice 1 — no route calls this yet — but implemented and tested
 * now because ProductionJobClaimService's automatic lease-expiry reclaim is
 * the same operation. Kept here, not inlined in the claim service, so a
 * future explicit "release" endpoint (Slice 2) has a single place to call.
 */
class ReleaseProductionJob
{
    public function handle(ProductionJob $job, ProductionDevice $device): ProductionJob
    {
        return DB::transaction(function () use ($job, $device) {
            /** @var ProductionJob $locked */
            $locked = ProductionJob::query()->whereKey($job->id)->lockForUpdate()->first()
                ?? throw new ModelNotFoundException;

            if ($locked->production_device_id !== $device->id) {
                return $locked;
            }

            $locked->update([
                'production_device_id' => null,
                'claimed_at' => null,
                'lease_expires_at' => null,
            ]);

            return $locked->fresh();
        });
    }
}
