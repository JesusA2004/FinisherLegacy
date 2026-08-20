<?php

namespace App\Exceptions\Production;

use App\Enums\DeviceErrorCode;
use App\Exceptions\Devices\DeviceApiException;

class JobAlreadyCompletedException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'Este trabajo de producción ya fue entregado.',
            DeviceErrorCode::JobAlreadyCompleted,
            409,
        );
    }
}
