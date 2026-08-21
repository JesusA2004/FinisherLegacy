<?php

namespace App\Http\Controllers\Api\V1\Devices;

use App\Actions\Devices\HeartbeatProductionDevice;
use App\Enums\ProductionJobStatus;
use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Devices\ProductionDeviceResource;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;
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

    /**
     * The bootstrap payload a Desktop needs on startup (docs/adr/0007
     * §Desktop bootstrap, docs/api/v1.md §Device bootstrap): device
     * identity, its active event/machine profile, whatever job it
     * currently holds (so a restarted client can reconcile instead of
     * guessing), server time, and the API contract version — never
     * laser/USB/driver data, which stays entirely local to the client.
     *
     * Deliberately a separate endpoint from `GET /device` rather than
     * reshaping it — `GET /device`'s flat `ProductionDeviceResource` shape
     * is already documented/shipped (docs/device-api/v1.md) and changing
     * it would be a breaking change for zero real benefit.
     */
    public function bootstrap(Request $request): JsonResponse
    {
        $device = $this->device($request);
        $device->loadMissing(['machineProfile', 'eventEdition']);

        $currentJob = ProductionJob::query()
            ->where('production_device_id', $device->id)
            ->whereNotIn('status', [ProductionJobStatus::Delivered, ProductionJobStatus::Failed, ProductionJobStatus::Cancelled])
            ->latest('claimed_at')
            ->first();

        return $this->respond([
            'device' => new ProductionDeviceResource($device),
            'current_job' => $currentJob === null ? null : [
                'id' => $currentJob->id,
                'status' => $currentJob->status->value,
                'next_action' => $currentJob->nextAction(),
            ],
            'server_time' => now()->toIso8601String(),
            'api_version' => config('finisher.api_version'),
            'minimum_supported_client_version' => config('finisher.minimum_supported_client_version'),
        ]);
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
