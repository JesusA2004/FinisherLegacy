<?php

namespace App\Exceptions\Devices;

use App\Enums\DeviceErrorCode;

class NoProductionJobAvailableException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'No hay trabajos disponibles.',
            DeviceErrorCode::NoProductionJobAvailable,
            404,
        );
    }
}
