<?php

namespace App\Actions\Production;

use App\Contracts\ProductionActor;
use App\Enums\ProductionJobStatus;
use App\Exceptions\Production\JobOwnedByOtherDeviceException;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;
use App\Services\Production\PlateProductionCoordinator;
use App\Services\Production\ProductionJobStateMachine;

/**
 * Shared base for every physical-workflow Action under app/Actions/Production
 * — both `App\Http\Controllers\ProductionController` (web, manual fallback)
 * and `App\Http\Controllers\Api\V1\Devices\ProductionJobController` (Device
 * API) call the exact same Action classes, never separate per-transport
 * implementations. See docs/adr/0003-production-state-machine.md §8.
 */
abstract class ProductionJobAction
{
    public function __construct(
        protected readonly ProductionJobStateMachine $stateMachine,
        protected readonly PlateProductionCoordinator $coordinator,
    ) {}

    /**
     * Only ProductionDevice actors are ownership-checked — a web operator
     * in manual mode is allowed to act on any job (it's the human fallback
     * path, not scoped to "your" claim) — see docs/adr/0003 §Web fallback.
     */
    protected function assertOwnership(ProductionJob $job, ProductionActor $actor): void
    {
        if ($actor instanceof ProductionDevice && $job->production_device_id !== $actor->id) {
            throw new JobOwnedByOtherDeviceException;
        }
    }

    /**
     * Validates, applies the status change + any extra attributes, and
     * syncs the Plate — the one place every Action ends up. Also refreshes
     * the device lease on every successful device-initiated call (not just
     * claim), so a job legitimately being worked step by step never
     * expires mid-flow — see docs/adr/0003 §Lease/renewal.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function transition(ProductionJob $job, ProductionJobStatus $to, array $attributes, ProductionActor $actor): ProductionJob
    {
        $this->stateMachine->assertAllowed($job, $to);

        // Only auto-refresh the lease if the caller didn't already decide
        // its value explicitly (ReleaseProductionJob clears it to null on
        // purpose — that must never be clobbered back to a future date).
        if ($actor instanceof ProductionDevice && ! array_key_exists('lease_expires_at', $attributes)) {
            $attributes['lease_expires_at'] = now()->addSeconds((int) config('finisher.device_lease_seconds', 900));
        }

        $job->update([...$attributes, 'status' => $to]);
        $job->refresh();

        $this->coordinator->sync($job);

        return $job;
    }
}
