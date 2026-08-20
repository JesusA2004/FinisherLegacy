<?php

namespace App\Exceptions\Production;

use App\Enums\DeviceErrorCode;
use App\Exceptions\Devices\DeviceApiException;

class InvalidProductionTransitionException extends DeviceApiException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(
            "No se puede pasar un trabajo de producción de \"{$from}\" a \"{$to}\".",
            DeviceErrorCode::InvalidProductionTransition,
            409,
            ['from' => $from, 'to' => $to],
        );
    }
}
