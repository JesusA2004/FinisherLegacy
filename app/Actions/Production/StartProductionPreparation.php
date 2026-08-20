<?php

namespace App\Actions\Production;

use App\Contracts\ProductionActor;
use App\Enums\ProductionJobStatus;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;
use App\Models\User;
use App\Services\Production\PlateProductionCoordinator;
use App\Services\Production\ProductionArtifactService;
use App\Services\Production\ProductionJobStateMachine;

/**
 * Two entry shapes into the same Action (docs/adr/0003 §Start preparation):
 * a device always arrives here already `Assigned` (it called claim() first);
 * a web operator in manual mode arrives at a `Queued` job with no prior
 * claim step, so this assigns it to them first. Either way, the artifact is
 * generated (if not already) the moment the job reaches Assigned — never
 * later, never re-rendered on a retry.
 */
class StartProductionPreparation extends ProductionJobAction
{
    public function __construct(
        ProductionJobStateMachine $stateMachine,
        PlateProductionCoordinator $coordinator,
        private readonly ProductionArtifactService $artifacts,
    ) {
        parent::__construct($stateMachine, $coordinator);
    }

    public function handle(ProductionJob $job, ProductionActor $actor): ProductionJob
    {
        $this->assertOwnership($job, $actor);

        if ($job->status === ProductionJobStatus::Queued) {
            $attributes = $actor instanceof ProductionDevice
                ? ['production_device_id' => $actor->id, 'claimed_at' => now()]
                : ['assigned_user_id' => $actor instanceof User ? $actor->id : null];

            $job = $this->transition($job, ProductionJobStatus::Assigned, $attributes, $actor);
        }

        $this->artifacts->ensureGenerated($job);

        return $this->transition(
            $job,
            ProductionJobStatus::Preparing,
            ['preparation_started_at' => $job->preparation_started_at ?? now()],
            $actor,
        );
    }
}
