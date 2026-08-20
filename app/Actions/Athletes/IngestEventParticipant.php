<?php

namespace App\Actions\Athletes;

use App\Enums\ResolveAthleteIdentityStatus;
use App\Models\EventParticipant;
use App\Support\Athletes\AthleteIdentityCandidateData;
use Illuminate\Support\Arr;

/**
 * Upsert-a-participant-row + resolve-its-identity, together — the one
 * place every participant entry point (today: CSV import; later: manual
 * admin creation, preregistration conversion) should call instead of
 * duplicating the upsert-then-match dance. See
 * docs/adr/0004-athlete-canonical-identity.md §70-75.
 */
class IngestEventParticipant
{
    public function __construct(
        private readonly ResolveAthleteIdentity $resolveIdentity,
        private readonly LinkParticipantToAthlete $linkParticipant,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  Must include event_edition_id, bib_number,
     *                                            first_name, last_name — everything else EventParticipant accepts is optional.
     */
    public function handle(array $attributes, string $sourceType, ?string $sourceReference = null): EventParticipant
    {
        $attributes['full_name'] ??= trim("{$attributes['first_name']} {$attributes['last_name']}");

        $participant = EventParticipant::updateOrCreate(
            ['event_edition_id' => $attributes['event_edition_id'], 'bib_number' => $attributes['bib_number']],
            Arr::except($attributes, ['event_edition_id', 'bib_number']),
        );

        $result = $this->resolveIdentity->handle(
            AthleteIdentityCandidateData::fromEventParticipant($participant),
            $sourceType,
            $sourceReference,
            $participant,
        );

        if (in_array($result->status, [ResolveAthleteIdentityStatus::Matched, ResolveAthleteIdentityStatus::Created], true)) {
            $this->linkParticipant->handle($participant, $result->athlete);
        }

        // Conflict: athlete_id stays null, an AthleteIdentityConflict was
        // recorded for review — the participant row itself is never
        // rejected (docs/adr/0004 §26, don't fail the whole import).
        return $participant->fresh();
    }
}
