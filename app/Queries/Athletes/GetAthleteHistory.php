<?php

namespace App\Queries\Athletes;

use App\Models\Athlete;
use Illuminate\Support\Collection;

/**
 * Everything a canonical Athlete has done across every event — the query
 * that proves "1 Athlete, N events" visually (docs/adr/0004 §57-60). Not a
 * public API yet, just the admin Athlete Detail screen for now.
 */
class GetAthleteHistory
{
    /**
     * @return array{participations: Collection, plates: Collection, medals: Collection}
     */
    public function handle(Athlete $athlete): array
    {
        return [
            'participations' => $athlete->eventParticipations()
                ->with(['eventEdition.event', 'eventRace', 'result'])
                ->orderByDesc('created_at')
                ->get(),
            'plates' => $athlete->plates()
                ->with(['eventEdition.event', 'legacyCode'])
                ->orderByDesc('created_at')
                ->get(),
            'medals' => $athlete->medals()
                ->with(['event', 'eventEdition'])
                ->orderByDesc('event_date')
                ->get(),
        ];
    }
}
