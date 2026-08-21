<?php

namespace App\Http\Resources\Api\V1\EventOps;

use App\Models\EventParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Search-result row — deliberately thin (no email/phone/birth_date, see
 * docs/api/v1.md §PII). Same fields the Web search endpoint has returned
 * since Slice 2. `has_plate` reads the `plates_exists` column
 * App\Queries\Operations\SearchEventParticipants eager-loads via
 * `withExists()` — never a per-row `Plate::where(...)->exists()` query
 * (docs/api/use-case-matrix.md §N+1 audit).
 *
 * @mixin EventParticipant
 */
class ParticipantSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bib_number' => $this->bib_number,
            'full_name' => $this->full_name,
            'race' => $this->eventRace?->name,
            'has_result' => $this->result !== null,
            'has_plate' => (bool) $this->plates_exists,
        ];
    }
}
