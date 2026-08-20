<?php

namespace App\Actions\Production;

use App\Contracts\ProductionActor;
use App\Enums\ProductionJobStatus;
use App\Models\ProductionJob;
use Illuminate\Database\Eloquent\Model;

/**
 * Only reachable from queued/assigned/preparing — the state machine itself
 * has no `cancelled` entry for any engraving state, so this is safe by
 * construction, not by an extra check here (see
 * docs/adr/0003-production-state-machine.md §Cancel/irreversibility).
 */
class CancelProductionJob extends ProductionJobAction
{
    public function handle(ProductionJob $job, ProductionActor $actor, ?string $reason = null): ProductionJob
    {
        $this->assertOwnership($job, $actor);

        $job = $this->transition($job, ProductionJobStatus::Cancelled, [], $actor);

        activity()
            ->causedBy($actor instanceof Model ? $actor : null)
            ->performedOn($job)
            ->withProperties(array_filter(['reason' => $reason]))
            ->log(ucfirst($actor->productionActorLabel())." canceló el trabajo de producción #{$job->id}.");

        return $job;
    }
}
