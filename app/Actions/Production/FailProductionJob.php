<?php

namespace App\Actions\Production;

use App\Contracts\ProductionActor;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Enums\ProductionJobStatus;
use App\Models\EventIncident;
use App\Models\ProductionJob;
use App\Models\User;
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

        // Surfaces in the incidents queue without a human having to
        // report it manually (docs/adr/0006-event-operations.md §8) —
        // `reported_by` stays null when the actor is a station, not a
        // User (App\Models\ProductionDevice never has an incidents author).
        EventIncident::create([
            'event_edition_id' => $job->event_edition_id,
            'event_participant_id' => $job->plate?->event_participant_id,
            'plate_id' => $job->plate_id,
            'reported_by' => $actor instanceof User ? $actor->id : null,
            'type' => IncidentType::PrintFailure,
            'description' => "Job #{$job->id} falló ({$data->errorCode->value}): {$data->message}",
            'status' => IncidentStatus::Open,
        ]);

        return $job;
    }
}
