<?php

namespace App\Exceptions\Production;

use App\Enums\DeviceErrorCode;
use App\Exceptions\Devices\DeviceApiException;

/**
 * A failed job never auto-resumes — see
 * docs/adr/0003-production-state-machine.md §Failure/Recovery. This is
 * what a device/operator gets if it tries to advance a job stuck in
 * `failed` instead of starting a retry/reprint.
 */
class JobFailedException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'Este trabajo de producción falló y no puede continuar — inicia un reintento o una reimpresión.',
            DeviceErrorCode::JobFailed,
            409,
        );
    }
}
