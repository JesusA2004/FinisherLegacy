<?php

namespace App\Exceptions\Production;

use App\Enums\DeviceErrorCode;
use App\Exceptions\Devices\DeviceApiException;

class BackNotCompletedException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'El reverso todavía no se ha grabado.',
            DeviceErrorCode::BackNotCompleted,
            409,
        );
    }
}
