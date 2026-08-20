<?php

namespace App\Exceptions\Devices;

use App\Enums\DeviceErrorCode;

/**
 * Not actually used as an HTTP error today — ConfirmDevicePairing returns a
 * normal `{data: {status: "pending"}}` response while waiting, since polling
 * during the expected wait for a Super Admin is not a failure. Kept as a
 * typed exception for callers that DO want to treat "still pending" as
 * exceptional (e.g. a future strict admin-side check).
 */
class PairingNotApprovedException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'La solicitud de emparejamiento todavía no ha sido aprobada.',
            DeviceErrorCode::PairingNotApproved,
            409,
        );
    }
}
