<?php

namespace App\Actions\Devices;

use App\Enums\DeviceAbility;
use App\Enums\DevicePairingStatus;
use App\Exceptions\Devices\PairingAlreadyCompletedException;
use App\Exceptions\Devices\PairingExpiredException;
use App\Exceptions\Devices\PairingRequestNotFoundException;
use App\Models\DevicePairingRequest;
use App\Models\ProductionDevice;
use App\Support\Devices\ConfirmDevicePairingData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Step 3 of pairing: the desktop polls with the private poll token it kept
 * from RequestDevicePairing. The Sanctum token is created HERE, at the
 * moment of first successful delivery, not earlier — so it exists in
 * plaintext for the shortest possible window (this request only) and is
 * never persisted or logged. See docs/adr/0002 §Pairing.
 */
class ConfirmDevicePairing
{
    /**
     * @return array{status: 'pending'}|array{status: 'completed', device: ProductionDevice, token: string}
     */
    public function handle(ConfirmDevicePairingData $data): array
    {
        $hash = hash('sha256', $data->pollToken);

        /** @var DevicePairingRequest|null $pairingRequest */
        $pairingRequest = DevicePairingRequest::query()->where('poll_token_hash', $hash)->first();

        if ($pairingRequest === null) {
            throw new PairingRequestNotFoundException;
        }

        if ($pairingRequest->status === DevicePairingStatus::Completed) {
            throw new PairingAlreadyCompletedException;
        }

        if ($pairingRequest->status === DevicePairingStatus::Expired || $pairingRequest->isExpired()) {
            if ($pairingRequest->status !== DevicePairingStatus::Expired) {
                $pairingRequest->update(['status' => DevicePairingStatus::Expired]);
            }

            throw new PairingExpiredException;
        }

        if ($pairingRequest->status === DevicePairingStatus::Pending) {
            return ['status' => 'pending'];
        }

        return DB::transaction(function () use ($pairingRequest) {
            /** @var DevicePairingRequest $locked */
            $locked = DevicePairingRequest::query()->whereKey($pairingRequest->id)->lockForUpdate()->first();

            // Re-check inside the lock: two simultaneous confirm polls for
            // the same pairing must never both deliver a token.
            if ($locked->status === DevicePairingStatus::Completed) {
                throw new PairingAlreadyCompletedException;
            }

            if ($locked->status !== DevicePairingStatus::Approved) {
                return ['status' => 'pending'];
            }

            /** @var ProductionDevice $device */
            $device = $locked->productionDevice()->firstOrFail();
            $token = $device->createToken('device:'.$device->id, DeviceAbility::all())->plainTextToken;

            $locked->update([
                'status' => DevicePairingStatus::Completed,
                'completed_at' => now(),
            ]);

            Log::info('production_device.pairing_completed', [
                'pairing_request_id' => $locked->id,
                'production_device_id' => $device->id,
            ]);

            return ['status' => 'completed', 'device' => $device, 'token' => $token];
        });
    }
}
