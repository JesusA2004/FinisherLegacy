<?php

namespace App\Http\Controllers\Api\V1\Devices;

use App\Actions\Devices\ClaimNextProductionJob;
use App\Actions\Devices\ReleaseProductionJob;
use App\Actions\Production\CancelProductionJob;
use App\Actions\Production\CompleteBackEngraving;
use App\Actions\Production\CompleteFrontEngraving;
use App\Actions\Production\ConfirmPlateFlip;
use App\Actions\Production\DeliverProductionPlate;
use App\Actions\Production\FailProductionJob;
use App\Actions\Production\StartBackEngraving;
use App\Actions\Production\StartFrontEngraving;
use App\Actions\Production\StartProductionPreparation;
use App\Actions\Production\VerifyProductionQr;
use App\Exceptions\Devices\ProductionJobForbiddenException;
use App\Exceptions\Production\ArtifactNotReadyException;
use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Devices\ProductionJobDeviceResource;
use App\Models\ProductionArtifact;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;
use App\Services\Devices\ProductionJobClaimService;
use App\Support\Devices\LaserJobPayload;
use App\Support\Production\FailProductionJobData;
use App\Support\Production\VerifyQrData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

/**
 * Every state-changing endpoint here calls the exact same Actions
 * (app/Actions/Production/*) the web fallback (App\Http\Controllers\
 * ProductionController) calls — see docs/adr/0003-production-state-machine.md
 * §8. This controller's job is transport plumbing (auth, JSON envelope,
 * ownership) only.
 */
class ProductionJobController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ProductionJobClaimService $claims,
        private readonly ClaimNextProductionJob $claimAction,
        private readonly ReleaseProductionJob $releaseAction,
        private readonly StartProductionPreparation $prepareAction,
        private readonly StartFrontEngraving $startFrontAction,
        private readonly CompleteFrontEngraving $completeFrontAction,
        private readonly ConfirmPlateFlip $confirmFlipAction,
        private readonly StartBackEngraving $startBackAction,
        private readonly CompleteBackEngraving $completeBackAction,
        private readonly VerifyProductionQr $verifyQrAction,
        private readonly DeliverProductionPlate $deliverAction,
        private readonly FailProductionJob $failAction,
        private readonly CancelProductionJob $cancelAction,
    ) {}

    /**
     * Read-only peek at the best candidate — never assigns it, never has
     * an artifact yet (see LaserJobPayload). `data` is `null` (not an
     * error) when the queue is empty.
     */
    public function next(Request $request): JsonResponse
    {
        $job = $this->claims->next($this->device($request));

        if ($job === null) {
            return $this->respond(null, 'No hay trabajos disponibles.');
        }

        return $this->respond(new ProductionJobDeviceResource(LaserJobPayload::fromJob($job)));
    }

    public function show(Request $request, ProductionJob $job): JsonResponse
    {
        $this->authorizeOwnership($request, $job);

        return $this->respond(new ProductionJobDeviceResource(LaserJobPayload::fromJob($job)));
    }

    public function claim(Request $request, ProductionJob $job): JsonResponse
    {
        $claimed = $this->claimAction->handle($job, $this->device($request));

        return $this->jobResponse($claimed, 'Trabajo reclamado.');
    }

    public function release(Request $request, ProductionJob $job): JsonResponse
    {
        $released = $this->releaseAction->handle($job, $this->device($request));

        return $this->jobResponse($released, 'Trabajo liberado.');
    }

    public function prepare(Request $request, ProductionJob $job): JsonResponse
    {
        $updated = $this->prepareAction->handle($job, $this->device($request));

        return $this->jobResponse($updated, 'Preparación iniciada.');
    }

    public function frontStart(Request $request, ProductionJob $job): JsonResponse
    {
        $updated = $this->startFrontAction->handle($job, $this->device($request));

        return $this->jobResponse($updated, 'Grabado del frente iniciado.');
    }

    public function frontComplete(Request $request, ProductionJob $job): JsonResponse
    {
        $updated = $this->completeFrontAction->handle($job, $this->device($request));

        return $this->jobResponse($updated, 'Frente grabado — voltea la placa.');
    }

    public function flipConfirm(Request $request, ProductionJob $job): JsonResponse
    {
        $updated = $this->confirmFlipAction->handle($job, $this->device($request));

        return $this->jobResponse($updated, 'Volteo confirmado.');
    }

    public function backStart(Request $request, ProductionJob $job): JsonResponse
    {
        $updated = $this->startBackAction->handle($job, $this->device($request));

        return $this->jobResponse($updated, 'Grabado del reverso iniciado.');
    }

    public function backComplete(Request $request, ProductionJob $job): JsonResponse
    {
        $updated = $this->completeBackAction->handle($job, $this->device($request));

        return $this->jobResponse($updated, 'Reverso grabado — verifica el QR.');
    }

    public function qrVerify(Request $request, ProductionJob $job): JsonResponse
    {
        $updated = $this->verifyQrAction->handle($job, $this->device($request), VerifyQrData::fromRequest($request));

        return $this->jobResponse($updated, 'QR verificado — placa lista.');
    }

    public function deliver(Request $request, ProductionJob $job): JsonResponse
    {
        $updated = $this->deliverAction->handle($job, $this->device($request));

        return $this->jobResponse($updated, 'Placa entregada.');
    }

    public function fail(Request $request, ProductionJob $job): JsonResponse
    {
        $updated = $this->failAction->handle($job, $this->device($request), FailProductionJobData::fromRequest($request));

        return $this->jobResponse($updated, 'Trabajo marcado como fallido.');
    }

    public function cancel(Request $request, ProductionJob $job): JsonResponse
    {
        $updated = $this->cancelAction->handle($job, $this->device($request));

        return $this->jobResponse($updated, 'Trabajo cancelado.');
    }

    public function artifact(Request $request, ProductionJob $job, string $face): HttpResponse
    {
        $this->authorizeOwnership($request, $job);

        abort_unless(in_array($face, ['front', 'back'], true), 422, 'Cara inválida.');

        /** @var ProductionArtifact|null $artifact */
        $artifact = $job->artifact()->first();

        if ($artifact === null) {
            throw new ArtifactNotReadyException;
        }

        $job->loadMissing('plate');

        return response($artifact->content($face), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="'.$job->plate->serial_number."_{$face}.svg\"",
        ]);
    }

    private function jobResponse(ProductionJob $job, string $message): JsonResponse
    {
        return $this->respond(new ProductionJobDeviceResource(LaserJobPayload::fromJob($job)), $message);
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
