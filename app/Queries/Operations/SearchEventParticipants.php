<?php

namespace App\Queries\Operations;

use App\Models\EventEdition;
use App\Models\EventParticipant;
use Illuminate\Database\Eloquent\Collection;

/**
 * The one participant-search implementation Web (App\Http\Controllers\OperatorController)
 * and the API (App\Http\Controllers\Api\V1\EventOpsController) both call —
 * see docs/api/use-case-matrix.md. Priority order (docs/adr/0006-event-operations.md
 * §3, §97 del prompt original): exact bib, then bib prefix, then external
 * participant id, then name — never fuzzy search, never a full-roster
 * scan into the client. `ORDER BY CASE` keeps this a single indexed-ish
 * query instead of N queries merged in PHP.
 */
class SearchEventParticipants
{
    /**
     * @return Collection<int, EventParticipant>
     */
    public function handle(EventEdition $edition, string $query, int $limit = 20): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return new Collection;
        }

        return EventParticipant::query()
            ->where('event_edition_id', $edition->id)
            ->where(function ($q) use ($query) {
                $q->where('bib_number', $query)
                    ->orWhere('bib_number', 'like', "{$query}%")
                    ->orWhere('external_participant_id', $query)
                    ->orWhere('full_name', 'like', "%{$query}%")
                    ->orWhere('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->orderByRaw(
                'CASE
                    WHEN bib_number = ? THEN 0
                    WHEN bib_number LIKE ? THEN 1
                    WHEN external_participant_id = ? THEN 2
                    ELSE 3
                END',
                [$query, "{$query}%", $query],
            )
            ->with(['eventRace', 'result'])
            ->withExists('plates')
            ->limit($limit)
            ->get();
    }
}
