<?php

namespace App\Http\Controllers\Api\V1\Devices;

use App\Actions\Devices\HeartbeatProductionDevice;
use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Devices\ProductionDeviceResource;
use App\Models\ProductionDevice;
use App\Support\Devices\HeartbeatDeviceData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly HeartbeatProductionDevice $heartbeat) {}

    public function show(Request $request): JsonResponse
    {
        $device = $this->device($request);
        $device->loadMissing(['machineProfile', 'eventEdition']);

        return $this->respond(new ProductionDeviceResource($device));
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $device = $this->heartbeat->handle($this->device($request), HeartbeatDeviceData::fromRequest($request));
        $device->loadMissing('eventEdition');

        return $this->respond([
            'server_time' => now()->toIso8601String(),
            'device' => new ProductionDeviceResource($device),
            'active_event' => $device->eventEdition === null ? null : [
                'id' => $device->eventEdition->id,
                'name' => $device->eventEdition->name,
            ],
            'status' => $device->status->value,
        ]);
    }

    /**
     * Real instanceof narrowing, not a docblock override — EnsureProductionDeviceToken
     * already guarantees this at the route level, this just gives every
     * caller here a properly typed value instead of `mixed`.
     */
    private function device(Request $request): ProductionDevice
    {
        $device = $request->user('sanctum');
        abort_unless($device instanceof ProductionDevice, 401);

        return $device;
    }
}
