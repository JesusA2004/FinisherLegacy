<?php

namespace App\Actions\Devices;

use App\Exceptions\Devices\ProductionJobForbiddenException;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;
use App\Services\Devices\ProductionJobClaimService;

/**
 * Claims one specific job — `POST /production/jobs/{job}/claim` — not "give
 * me whatever's next" (that's the read-only `GET .../jobs/next` peek). A
 * device normally calls peek-then-claim-that-id, but claim() re-verifies
 * availability under a row lock regardless of what the device saw at peek
 * time (see ProductionJobClaimService).
 */
class ClaimNextProductionJob
{
    public function __construct(private readonly ProductionJobClaimService $claims) {}

    public function handle(ProductionJob $job, ProductionDevice $device): ProductionJob
    {
        // Revoked is already blocked at the middleware level
        // (EnsureProductionDeviceToken) — this additionally requires the
        // device to have heartbeat'd recently before it's trusted to start
        // physical work. See docs/adr/0003-production-state-machine.md §81.
        abort_unless($device->isOnline(), 409, 'El dispositivo debe enviar un heartbeat reciente antes de reclamar un trabajo.');

        if ($device->event_edition_id !== null
            && $job->event_edition_id !== null
            && $job->event_edition_id !== $device->event_edition_id) {
            throw new ProductionJobForbiddenException;
        }

        // A job with no machine_profile_id is generic — any station may
        // take it. One WITH a profile requires a matching device. See
        // docs/adr/0003-production-state-machine.md §59.
        if ($device->machine_profile_id !== null
            && $job->machine_profile_id !== null
            && $job->machine_profile_id !== $device->machine_profile_id) {
            throw new ProductionJobForbiddenException;
        }

        return $this->claims->claim($job, $device);
    }
}
