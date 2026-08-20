<?php

namespace App\Queries\Production;

use App\Enums\ProductionJobStatus;
use App\Models\EventEdition;
use App\Models\ProductionJob;
use Illuminate\Support\Collection;

/**
 * Real measured durations from the timestamp columns ProductionJob has
 * carried since Slice 2 — no aggregate table, SQL/PHP computes it on read
 * (docs/adr/0006-event-operations.md §9). Averaged in PHP rather than a
 * database-specific date-diff function, so this behaves identically on
 * sqlite (tests) and MySQL (production). Returns null when nothing has
 * been delivered yet — never promises a number from zero data (§75 del
 * prompt: no prometer 30 segundos, medir lo real).
 */
class GetProductionMetrics
{
    /**
     * @return array{queue_wait: ?int, front_duration: ?int, flip_delay: ?int, back_duration: ?int, qr_delay: ?int, total_duration: ?int, sample_size: int}|null
     */
    public function handle(EventEdition $edition): ?array
    {
        $jobs = ProductionJob::query()
            ->where('event_edition_id', $edition->id)
            ->where('status', ProductionJobStatus::Delivered)
            ->get();

        if ($jobs->isEmpty()) {
            return null;
        }

        return [
            'queue_wait' => $this->average($jobs, 'queued_at', 'claimed_at'),
            'front_duration' => $this->average($jobs, 'front_started_at', 'front_engraved_at'),
            'flip_delay' => $this->average($jobs, 'flip_confirmed_at', 'back_started_at'),
            'back_duration' => $this->average($jobs, 'back_started_at', 'back_engraved_at'),
            'qr_delay' => $this->average($jobs, 'qr_verified_at', 'ready_at'),
            'total_duration' => $this->average($jobs, 'queued_at', 'delivered_at'),
            'sample_size' => $jobs->count(),
        ];
    }

    /**
     * @param  Collection<int, ProductionJob>  $jobs
     */
    private function average(Collection $jobs, string $startColumn, string $endColumn): ?int
    {
        $diffs = $jobs
            ->filter(fn (ProductionJob $job) => $job->{$startColumn} !== null && $job->{$endColumn} !== null)
            ->map(fn (ProductionJob $job) => $job->{$endColumn}->diffInSeconds($job->{$startColumn}, absolute: true));

        return $diffs->isEmpty() ? null : (int) round($diffs->average());
    }
}
