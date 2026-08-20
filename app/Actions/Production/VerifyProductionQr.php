<?php

namespace App\Actions\Production;

use App\Contracts\ProductionActor;
use App\Enums\ProductionJobStatus;
use App\Exceptions\Production\BackNotCompletedException;
use App\Exceptions\Production\QrVerificationFailedException;
use App\Models\ProductionJob;
use App\Services\Production\PlateProductionCoordinator;
use App\Services\Production\ProductionJobStateMachine;
use App\Support\Production\LegacyQrVerifier;
use App\Support\Production\VerifyQrData;

/**
 * Never accepts a bare `qr_verified=true` — the caller must hand over what
 * was actually scanned, checked here against the plate's real Legacy Code.
 * A wrong scan does NOT transition the job: it stays `verifying_qr`,
 * `qr_verified_at` stays null, and the caller gets QR_VERIFICATION_FAILED
 * — never a 200 with a lie. See
 * docs/adr/0003-production-state-machine.md §QR verification.
 */
class VerifyProductionQr extends ProductionJobAction
{
    public function __construct(
        ProductionJobStateMachine $stateMachine,
        PlateProductionCoordinator $coordinator,
        private readonly LegacyQrVerifier $verifier,
    ) {
        parent::__construct($stateMachine, $coordinator);
    }

    public function handle(ProductionJob $job, ProductionActor $actor, VerifyQrData $data): ProductionJob
    {
        $this->assertOwnership($job, $actor);

        if ($job->back_engraved_at === null) {
            throw new BackNotCompletedException;
        }

        $job->loadMissing('plate.legacyCode');
        $expected = $job->plate->legacyCode?->code;

        if ($expected === null || ! $this->verifier->matches($data->decodedValue, $expected)) {
            $job->update(['qr_decoded_value' => $data->decodedValue]);

            throw new QrVerificationFailedException;
        }

        return $this->transition(
            $job,
            ProductionJobStatus::Ready,
            [
                'qr_verified_at' => now(),
                'qr_decoded_value' => $data->decodedValue,
                'ready_at' => now(),
                ...ProductionJob::actorAttributes('qr', $actor),
            ],
            $actor,
        );
    }
}
