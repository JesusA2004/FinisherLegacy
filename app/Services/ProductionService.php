<?php

namespace App\Services;

use App\Enums\PlateStatus;
use App\Enums\ProductionJobStatus;
use App\Models\Plate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every plate status change on the /production kanban goes through here —
 * the board never mutates a card's column client-side without the backend
 * confirming the move is legal, per a single allowed-transitions map.
 */
class ProductionService
{
    /**
     * Deliberately matches the /production kanban's 5 visible columns
     * one-to-one — quality_check/produced exist on PlateStatus for future
     * granularity but aren't surfaced as separate columns yet, so they're
     * not reachable from here until there's a real operational need.
     */
    private const TRANSITIONS = [
        'draft' => ['queued', 'cancelled'],
        'pending_confirmation' => ['queued', 'cancelled'],
        'queued' => ['processing', 'cancelled'],
        'processing' => ['ready', 'cancelled'],
        'quality_check' => ['ready', 'cancelled'],
        'produced' => ['ready', 'cancelled'],
        'ready' => ['delivered', 'reprint'],
        'delivered' => ['reprint'],
        'reprint' => ['queued'],
        'cancelled' => [],
    ];

    /**
     * Coarse buckets for the kanban board — several fine-grained
     * PlateStatus values map to the same visible column.
     */
    public const COLUMNS = [
        'pending' => ['draft', 'pending_confirmation', 'queued'],
        'processing' => ['processing', 'quality_check'],
        'ready' => ['produced', 'ready'],
        'delivered' => ['delivered'],
        'issue' => ['cancelled', 'reprint'],
    ];

    public function transition(Plate $plate, PlateStatus $to, User $actor): Plate
    {
        $allowed = self::TRANSITIONS[$plate->status->value];

        if (! in_array($to->value, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ["No se puede mover una placa de \"{$plate->status->value}\" a \"{$to->value}\"."],
            ]);
        }

        return DB::transaction(function () use ($plate, $to, $actor) {
            $attributes = ['status' => $to];

            if ($to === PlateStatus::Produced) {
                $attributes['produced_at'] = now();
            }

            if ($to === PlateStatus::Delivered) {
                $attributes['delivered_at'] = now();
            }

            $plate->update($attributes);

            $this->syncLatestJob($plate, $to, $actor);

            return $plate->fresh(['latestProductionJob']);
        });
    }

    private function syncLatestJob(Plate $plate, PlateStatus $to, User $actor): void
    {
        $job = $plate->latestProductionJob;

        if (! $job) {
            return;
        }

        $jobStatus = match ($to) {
            PlateStatus::Processing, PlateStatus::QualityCheck => ProductionJobStatus::Processing,
            PlateStatus::Produced, PlateStatus::Ready, PlateStatus::Delivered => ProductionJobStatus::Completed,
            PlateStatus::Cancelled => ProductionJobStatus::Cancelled,
            default => $job->status,
        };

        $jobAttributes = ['status' => $jobStatus, 'assigned_user_id' => $actor->id];

        if ($jobStatus === ProductionJobStatus::Processing && ! $job->started_at) {
            $jobAttributes['started_at'] = now();
        }

        if ($jobStatus === ProductionJobStatus::Completed && ! $job->completed_at) {
            $jobAttributes['completed_at'] = now();
        }

        $job->update($jobAttributes);
    }
}
