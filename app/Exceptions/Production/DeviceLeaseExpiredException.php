<?php

namespace App\Exceptions\Production;

use App\Enums\DeviceErrorCode;
use App\Exceptions\Devices\DeviceApiException;

/**
 * The device still holds `production_device_id` on this job, but its lease
 * lapsed before it started preparing — another device could grab it at any
 * moment. Thrown only in that narrow window (Assigned, lease past due,
 * nothing physical started yet); never once engraving is underway, since
 * leases stop applying at that point (docs/adr/0003 §Lease).
 */
class DeviceLeaseExpiredException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'Tu lease sobre este trabajo expiró — vuelve a reclamarlo antes de continuar.',
            DeviceErrorCode::DeviceLeaseExpired,
            409,
        );
    }
}
