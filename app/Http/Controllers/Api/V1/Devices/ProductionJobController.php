<?php

namespace App\Http\Controllers\Api\V1\Devices;

use App\Actions\Devices\ClaimNextProductionJob;
use App\Exceptions\Devices\ProductionJobForbiddenException;
use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Devices\ProductionJobDeviceResource;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;
use App\Services\Devices\ProductionJobClaimService;
use App\Services\PlateExportService;
use App\Support\Devices\LaserJobPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

class ProductionJobController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ProductionJobClaimService $claims,
        private readonly ClaimNextProductionJob $claimAction,
        private readonly PlateExportService $exporter,
    ) {}

    /**
     * Read-only peek at the best candidate — never assigns it. `data` is
     * `null` (not an error) when the queue is empty; an empty queue is a
     * normal state a device polls through, not a failure.
     */
    public function next(Request $request): JsonResponse
    {
        $job = $this->claims->next($this->device($request));

        if ($job === null) {
            return $this->respond(null, 'No hay trabajos disponibles.');
        }

        return $this->respond(new ProductionJobDeviceResource(LaserJobPayload::fromJob($job, $this->exporter)));
    }

    public function show(Request $request, ProductionJob $job): JsonResponse
    {
        $this->authorizeOwnership($request, $job);

        return $this->respond(new ProductionJobDeviceResource(LaserJobPayload::fromJob($job, $this->exporter)));
    }

    public function claim(Request $request, ProductionJob $job): JsonResponse
    {
        $claimed = $this->claimAction->handle($job, $this->device($request));

        return $this->respond(
            new ProductionJobDeviceResource(LaserJobPayload::fromJob($claimed, $this->exporter)),
            'Trabajo reclamado.',
        );
    }

    public function artifact(Request $request, ProductionJob $job, string $face): HttpResponse
    {
        $this->authorizeOwnership($request, $job);

        abort_unless(in_array($face, ['front', 'back'], true), 422, 'Cara inválida.');

        $job->loadMissing('plate');
        $export = $this->exporter->exportFace($job->plate, $face, 'svg');

        return response($export['content'], 200, [
            'Content-Type' => $export['mime'],
            'Content-Disposition' => 'inline; filename="'.$job->plate->serial_number."_{$face}.svg\"",
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

    private function authorizeOwnership(Request $request, ProductionJob $job): void
    {
        if ($job->production_device_id !== $this->device($request)->id) {
            throw new ProductionJobForbiddenException;
        }
    }
}
