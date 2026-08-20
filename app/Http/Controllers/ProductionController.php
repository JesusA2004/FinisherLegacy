<?php

namespace App\Http\Controllers;

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
use App\Enums\ProductionJobStatus;
use App\Exceptions\Devices\DeviceApiException;
use App\Models\ProductionJob;
use App\Support\Production\FailProductionJobData;
use App\Support\Production\VerifyQrData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The manual/web fallback for the exact same physical workflow the Device
 * API drives — every method here calls the same Action classes as
 * App\Http\Controllers\Api\V1\Devices\ProductionJobController, never a
 * second implementation of the rules. See
 * docs/adr/0003-production-state-machine.md §8, §45 Web fallback.
 */
class ProductionController extends Controller
{
    private const COLUMNS = [
        'pending' => ['queued'],
        'processing' => ['assigned', 'preparing', 'engraving_front', 'awaiting_flip', 'engraving_back', 'verifying_qr'],
        'ready' => ['ready'],
        'delivered' => ['delivered'],
        'issue' => ['failed', 'cancelled'],
    ];

    public function __construct(
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

    public function index(): Response
    {
        $jobs = ProductionJob::query()
            ->whereHas('plate', fn ($q) => $q->whereNotNull('legacy_code_id'))
            ->with(['plate.legacyCode', 'plate.eventEdition.event', 'productionDevice'])
            ->orderByDesc('updated_at')
            ->limit(300)
            ->get();

        $columns = [];

        foreach (self::COLUMNS as $key => $statuses) {
            $columns[$key] = $jobs
                ->whereIn('status', array_map(fn (string $s) => ProductionJobStatus::from($s), $statuses))
                ->values()
                ->map(fn (ProductionJob $job) => $this->cardPayload($job));
        }

        return Inertia::render('production/Index', ['columns' => $columns]);
    }

    public function prepare(Request $request, ProductionJob $job): RedirectResponse
    {
        return $this->run(fn () => $this->prepareAction->handle($job, $request->user()));
    }

    public function frontStart(Request $request, ProductionJob $job): RedirectResponse
    {
        return $this->run(fn () => $this->startFrontAction->handle($job, $request->user()));
    }

    public function frontComplete(Request $request, ProductionJob $job): RedirectResponse
    {
        return $this->run(fn () => $this->completeFrontAction->handle($job, $request->user()));
    }

    public function flipConfirm(Request $request, ProductionJob $job): RedirectResponse
    {
        return $this->run(fn () => $this->confirmFlipAction->handle($job, $request->user()));
    }

    public function backStart(Request $request, ProductionJob $job): RedirectResponse
    {
        return $this->run(fn () => $this->startBackAction->handle($job, $request->user()));
    }

    public function backComplete(Request $request, ProductionJob $job): RedirectResponse
    {
        return $this->run(fn () => $this->completeBackAction->handle($job, $request->user()));
    }

    public function qrVerify(Request $request, ProductionJob $job): RedirectResponse
    {
        $data = VerifyQrData::fromRequest($request);

        return $this->run(fn () => $this->verifyQrAction->handle($job, $request->user(), $data));
    }

    public function deliver(Request $request, ProductionJob $job): RedirectResponse
    {
        return $this->run(fn () => $this->deliverAction->handle($job, $request->user()));
    }

    public function fail(Request $request, ProductionJob $job): RedirectResponse
    {
        $data = FailProductionJobData::fromRequest($request);

        return $this->run(fn () => $this->failAction->handle($job, $request->user(), $data));
    }

    public function cancel(Request $request, ProductionJob $job): RedirectResponse
    {
        return $this->run(fn () => $this->cancelAction->handle($job, $request->user()));
    }

    private function run(callable $action): RedirectResponse
    {
        try {
            $action();
        } catch (DeviceApiException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function cardPayload(ProductionJob $job): array
    {
        $plate = $job->plate;

        return [
            'id' => $job->id,
            'status' => $job->status->value,
            'manual_action' => $this->manualNextAction($job),
            'serial_number' => $plate->serial_number,
            'athlete_name' => $plate->athlete_name,
            'bib_number' => $plate->bib_number,
            'event_name' => $plate->eventEdition?->event->name ?? $plate->event_name,
            'legacy_code' => $plate->legacyCode?->code,
            'generation_mode' => $plate->generation_mode->value,
            'updated_at' => $job->updated_at?->diffForHumans(),
            'download_format' => $plate->eventEdition->production_export_format ?? 'svg',
            'download_dpi' => $plate->eventEdition->default_dpi ?? 300,
            'device' => $job->productionDevice ? [
                'name' => $job->productionDevice->name,
                'online' => $job->productionDevice->isOnline(),
            ] : null,
            'checklist' => [
                'front' => $job->front_engraved_at !== null,
                'back' => $job->back_engraved_at !== null,
                'qr' => $job->qr_verified_at !== null,
            ],
            'error_message' => $job->error_message,
        ];
    }

    /**
     * Unlike ProductionJob::nextAction() (Device API — never represents
     * "claim", a device-only concept), the web fallback's `queued` column
     * DOES have a manual action: `prepare` handles the implicit
     * assign-to-me-and-start-preparing hop for an operator with no device
     * involved. See docs/adr/0003 §Web fallback.
     */
    private function manualNextAction(ProductionJob $job): ?string
    {
        return $job->status === ProductionJobStatus::Queued ? 'prepare' : $job->nextAction();
    }
}
