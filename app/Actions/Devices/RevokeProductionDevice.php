<?php

namespace App\Actions\Devices;

use App\Enums\ProductionDeviceStatus;
use App\Models\ProductionDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Deletes every Sanctum token the device holds (immediate effect — the next
 * request with that token 401s) and marks it Revoked so the admin UI and
 * any defense-in-depth status check (EnsureProductionDeviceToken) agree.
 */
class RevokeProductionDevice
{
    public function handle(ProductionDevice $device): ProductionDevice
    {
        DB::transaction(function () use ($device) {
            $device->tokens()->delete();
            $device->update(['status' => ProductionDeviceStatus::Revoked]);
        });

        Log::info('production_device.revoked', ['production_device_id' => $device->id]);

        return $device->fresh();
    }
}
