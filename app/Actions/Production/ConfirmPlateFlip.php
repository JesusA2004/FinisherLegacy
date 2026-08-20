<?php

namespace App\Actions\Production;

use App\Contracts\ProductionActor;
use App\Enums\ProductionJobStatus;
use App\Exceptions\Production\FrontNotCompletedException;
use App\Exceptions\Production\InvalidProductionTransitionException;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;

/**
 * Deliberately not a status transition — the job stays `awaiting_flip`.
 * This just records the real-world event ("the operator actually flipped
 * the plate in the jig") that StartBackEngraving requires as a
 * precondition, so finishing the front never silently implies the flip
 * happened — see docs/adr/0003-production-state-machine.md §Flip.
 */
class ConfirmPlateFlip extends ProductionJobAction
{
    public function handle(ProductionJob $job, ProductionActor $actor): ProductionJob
    {
        $this->assertOwnership($job, $actor);

        if ($job->status !== ProductionJobStatus::AwaitingFlip) {
            throw new InvalidProductionTransitionException($job->status->value, ProductionJobStatus::AwaitingFlip->value);
        }

        // Defensive, belt-and-suspenders check beyond the status guard
        // above — the state machine can only ever reach AwaitingFlip via
        // CompleteFrontEngraving (which sets this), but a clear
        // FRONT_NOT_COMPLETED is more useful than a generic transition
        // error if that invariant is ever violated.
        if ($job->front_engraved_at === null) {
            throw new FrontNotCompletedException;
        }

        $attributes = [
            'flip_confirmed_at' => now(),
            ...ProductionJob::actorAttributes('flip', $actor),
        ];

        if ($actor instanceof ProductionDevice) {
            $attributes['lease_expires_at'] = now()->addSeconds((int) config('finisher.device_lease_seconds', 900));
        }

        $job->update($attributes);
        $job->refresh();

        return $job;
    }
}
