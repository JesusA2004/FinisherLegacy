<?php

namespace App\Actions\Devices;

use App\Enums\ProductionDeviceStatus;
use App\Exceptions\Devices\DeviceRevokedException;
use App\Models\ProductionDevice;
use App\Support\Devices\HeartbeatDeviceData;

class HeartbeatProductionDevice
{
    public function handle(ProductionDevice $device, HeartbeatDeviceData $data): ProductionDevice
    {
        // Defense in depth — EnsureProductionDeviceToken already blocks a
        // revoked device before this runs, but revocation must be
        // effective immediately even for an in-flight request.
        if ($device->status === ProductionDeviceStatus::Revoked) {
            throw new DeviceRevokedException;
        }

        $device->update(array_filter([
            'last_seen_at' => now(),
            'app_version' => $data->appVersion,
            'capabilities' => $data->capabilities,
        ], fn ($value) => $value !== null));

        return $device->fresh();
    }
}
