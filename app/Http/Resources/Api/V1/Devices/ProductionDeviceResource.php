<?php

namespace App\Http\Resources\Api\V1\Devices;

use App\Models\ProductionDevice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductionDevice
 */
class ProductionDeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'station_code' => $this->station_code,
            'status' => $this->status->value,
            'online' => $this->isOnline(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'app_version' => $this->app_version,
            'capabilities' => $this->capabilities,
            'machine_profile' => $this->whenLoaded('machineProfile', fn () => [
                'id' => $this->machineProfile->id,
                'name' => $this->machineProfile->name,
            ]),
            'event_edition' => $this->whenLoaded('eventEdition', fn () => $this->eventEdition === null ? null : [
                'id' => $this->eventEdition->id,
                'name' => $this->eventEdition->name,
            ]),
        ];
    }
}
