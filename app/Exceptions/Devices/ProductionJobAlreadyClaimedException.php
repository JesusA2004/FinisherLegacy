<?php

namespace App\Exceptions\Devices;

use App\Enums\DeviceErrorCode;

class ProductionJobAlreadyClaimedException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'Otro dispositivo ya reclamó este trabajo.',
            DeviceErrorCode::ProductionJobAlreadyClaimed,
            409,
        );
    }
}
