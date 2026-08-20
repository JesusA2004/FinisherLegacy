<?php

namespace App\Actions\Production;

use App\Contracts\ProductionActor;
use App\Enums\ProductionJobStatus;
use App\Models\ProductionJob;
use App\Support\Production\FailProductionJobData;
use Illuminate\Database\Eloquent\Model;

/**
 * A failed job never auto-resumes and never rewrites what already happened
 * physically — `front_engraved_at`/`back_engraved_at` are untouched here,
 * on purpose (a DB rollback cannot un-engrave metal). See
 * docs/adr/0003-production-state-machine.md §Failure/Recovery. Recovering
 * means App\Actions\Production\CancelProductionJob won't apply (job is
 * past that window) — a human decides retry (new job, Slice 3) or reprint.
 */
class FailProductionJob extends ProductionJobAction
{
    public function handle(ProductionJob $job, ProductionActor $actor, FailProductionJobData $data): ProductionJob
    {
        $this->assertOwnership($job, $actor);

        // "attempt" = one complete physical execution (docs/adr/0003 §53) —
        // only counts if engraving genuinely started, not if it failed
        // while still being prepared.
        $physicalAttemptStarted = $job->front_started_at !== null;

        $job = $this->transition($job, ProductionJobStatus::Failed, [
            'error_code' => $data->errorCode->value,
            'error_message' => $data->message,
            'attempts' => $physicalAttemptStarted ? $job->attempts + 1 : $job->attempts,
        ], $actor);

        activity()
            ->causedBy($actor instanceof Model ? $actor : null)
            ->performedOn($job)
            ->withProperties(['error_code' => $data->errorCode->value, 'metadata' => $data->metadata])
            ->log(ucfirst($actor->productionActorLabel())." marcó como fallido el trabajo de producción #{$job->id} ({$data->errorCode->value}).");

        return $job;
    }
}
