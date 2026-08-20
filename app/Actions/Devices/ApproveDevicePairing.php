<?php

namespace App\Actions\Devices;

use App\Enums\DevicePairingStatus;
use App\Enums\ProductionDeviceStatus;
use App\Models\DevicePairingRequest;
use App\Models\ProductionDevice;
use App\Models\User;
use App\Support\Devices\ApproveDevicePairingData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Step 2 of pairing: a Super Admin links a pending request to a
 * ProductionDevice (creating it if this is a first-time pairing). Does NOT
 * issue a token — that only happens once, at first successful
 * ConfirmDevicePairing, to keep the plaintext token's exposure window as
 * small as possible.
 */
class ApproveDevicePairing
{
    public function handle(DevicePairingRequest $pairingRequest, ApproveDevicePairingData $data, User $admin): ProductionDevice
    {
        if ($pairingRequest->isExpired()) {
            $pairingRequest->update(['status' => DevicePairingStatus::Expired]);

            throw ValidationException::withMessages([
                'pairing' => ['Este código de emparejamiento ya expiró.'],
            ]);
        }

        if ($pairingRequest->status !== DevicePairingStatus::Pending) {
            throw ValidationException::withMessages([
                'pairing' => ['Esta solicitud ya fue procesada.'],
            ]);
        }

        return DB::transaction(function () use ($pairingRequest, $data, $admin) {
            $device = ProductionDevice::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data->nameOverride ?? $pairingRequest->requested_name,
                'station_code' => $pairingRequest->requested_station_code,
                'machine_profile_id' => $data->machineProfileId,
                'event_edition_id' => $data->eventEditionId,
                'status' => ProductionDeviceStatus::Active,
                'app_version' => $pairingRequest->requested_app_version,
                'capabilities' => $pairingRequest->requested_capabilities,
            ]);

            $pairingRequest->update([
                'status' => DevicePairingStatus::Approved,
                'production_device_id' => $device->id,
                'machine_profile_id' => $data->machineProfileId,
                'event_edition_id' => $data->eventEditionId,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            return $device;
        });
    }
}
