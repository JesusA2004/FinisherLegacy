<?php

namespace App\Exceptions\Production;

use App\Enums\DeviceErrorCode;
use App\Exceptions\Devices\DeviceApiException;

class FrontNotCompletedException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'El frente todavía no se ha grabado.',
            DeviceErrorCode::FrontNotCompleted,
            409,
        );
    }
}
