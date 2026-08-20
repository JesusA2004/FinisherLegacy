<?php

namespace App\Exceptions\Production;

use App\Enums\DeviceErrorCode;
use App\Exceptions\Devices\DeviceApiException;

class JobNotAssignedException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'Este trabajo todavía no está asignado a ningún dispositivo.',
            DeviceErrorCode::JobNotAssigned,
            409,
        );
    }
}
