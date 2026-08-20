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
        if ($device->event_edition_id !== null
            && $job->event_edition_id !== null
            && $job->event_edition_id !== $device->event_edition_id) {
            throw new ProductionJobForbiddenException;
        }

        return $this->claims->claim($job, $device);
    }
}
