<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Devices\ApproveDevicePairing;
use App\Actions\Devices\RevokeProductionDevice;
use App\Enums\DevicePairingStatus;
use App\Http\Controllers\Controller;
use App\Models\DevicePairingRequest;
use App\Models\EventEdition;
use App\Models\MachineProfile;
use App\Models\ProductionDevice;
use App\Support\Devices\ApproveDevicePairingData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Estaciones" — Super Admin only (routes/web.php gates this on
 * `productiondevices.*`, which only the `admin` role's full permission set
 * and the super_admin Gate::before bypass reach). Never shows a device's
 * Sanctum token — it was delivered once, directly to the desktop, in
 * ConfirmDevicePairing.
 */
class ProductionDeviceController extends Controller
{
    public function __construct(
        private readonly ApproveDevicePairing $approvePairing,
        private readonly RevokeProductionDevice $revokeDevice,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/production-devices/Index', [
            'devices' => ProductionDevice::query()
                ->with(['machineProfile', 'eventEdition'])
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (ProductionDevice $device) => [
                    'id' => $device->id,
                    'uuid' => $device->uuid,
                    'name' => $device->name,
                    'station_code' => $device->station_code,
                    'status' => $device->status->value,
                    'online' => $device->isOnline(),
                    'last_seen_at' => $device->last_seen_at?->diffForHumans(),
                    'app_version' => $device->app_version,
                    'capabilities' => $device->capabilities,
                    'machine_profile' => $device->machineProfile?->name,
                    'event_edition' => $device->eventEdition?->name,
                ]),
            'pendingPairings' => DevicePairingRequest::query()
                ->where('status', DevicePairingStatus::Pending)
                ->where('expires_at', '>', now())
                ->orderBy('created_at')
                ->get()
                ->map(fn (DevicePairingRequest $pairing) => [
                    'id' => $pairing->id,
                    'code' => $pairing->code,
                    'requested_name' => $pairing->requested_name,
                    'requested_app_version' => $pairing->requested_app_version,
                    'expires_at' => $pairing->expires_at->diffForHumans(),
                ]),
            'machineProfiles' => MachineProfile::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'eventEditions' => EventEdition::query()->with('event')->orderByDesc('event_date')->limit(50)->get()
                ->map(fn (EventEdition $edition) => ['id' => $edition->id, 'name' => $edition->event->name.' — '.$edition->name]),
        ]);
    }

    public function approvePairing(Request $request, DevicePairingRequest $pairingRequest): RedirectResponse
    {
        $data = ApproveDevicePairingData::fromRequest($request);

        $this->approvePairing->handle($pairingRequest, $data, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estación vinculada.']);

        return back();
    }

    public function revoke(ProductionDevice $device): RedirectResponse
    {
        $this->revokeDevice->handle($device);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estación revocada.']);

        return back();
    }
}
