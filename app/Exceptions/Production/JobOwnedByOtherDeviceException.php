<?php

namespace App\Exceptions\Production;

use App\Enums\DeviceErrorCode;
use App\Exceptions\Devices\DeviceApiException;

/**
 * Distinct from App\Exceptions\Devices\ProductionJobForbiddenException
 * (artifact download, Slice 1) — same idea, separate code, so a Slice 1
 * client parsing PRODUCTION_JOB_FORBIDDEN isn't affected by Slice 2 adding
 * ownership checks to the new command endpoints.
 */
class JobOwnedByOtherDeviceException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'Este trabajo de producción está asignado a otro dispositivo.',
            DeviceErrorCode::JobOwnedByOtherDevice,
            403,
        );
    }
}
