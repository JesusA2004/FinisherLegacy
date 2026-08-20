<?php

namespace App\Exceptions\Devices;

use App\Enums\DeviceErrorCode;

/**
 * The job exists but belongs to another device's claim — e.g. Device B
 * trying to download Device A's artifact. Never reveals which device holds
 * it.
 */
class ProductionJobForbiddenException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'Este trabajo de producción no está asignado a tu dispositivo.',
            DeviceErrorCode::ProductionJobForbidden,
            403,
        );
    }
}
