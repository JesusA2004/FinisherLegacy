<?php

namespace App\Services;

use App\Models\EventParticipant;
use App\Models\EventResult;
use App\Support\RaceDistanceFormatter;

/**
 * The only place an EventParticipant + EventRace + EventResult (+ its
 * splits) turn into the `dynamic_fields` snapshot a Plate freezes. Used by
 * PlateGenerationService::generateIntegrated() at creation time and by an
 * explicit "actualizar con datos actuales" reprint — never by Quick (that
 * snapshot comes from what the operator typed by hand, not from a result).
 *
 * A Plate's dynamic_fields never changes on its own after this runs: if the
 * source EventResult changes later, the plate keeps showing what was true
 * when it was made, until someone explicitly asks to refresh it.
 */
class PlateSnapshotBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(EventParticipant $participant): array
    {
        $participant->loadMissing(['eventRace', 'result.splits']);
        $result = $participant->result;

        return [
            'distance' => RaceDistanceFormatter::format($participant->eventRace),
            'swim_time' => $this->splitTime($result, 'swim'),
            'bike_time' => $this->splitTime($result, 'bike'),
            'run_time' => $this->splitTime($result, 'run'),
            'overall_position' => $result?->overall_position,
            'category' => $participant->category,
            'category_position' => $result?->category_position,
        ];
    }

    private function splitTime(?EventResult $result, string $type): ?string
    {
        if ($result === null) {
            return null;
        }

        $split = $result->splits->firstWhere('type', $type);

        if ($split === null) {
            return null;
        }

        return $split->segment_time ?? $split->elapsed_time;
    }
}
