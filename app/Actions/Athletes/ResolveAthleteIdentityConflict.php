<?php

namespace App\Actions\Athletes;

use App\Enums\AthleteIdentityConflictStatus;
use App\Models\Athlete;
use App\Models\AthleteIdentityConflict;
use App\Models\User;
use App\Support\Athletes\AthleteIdentityCandidateData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The three actions the /admin/identity-conflicts screen offers (§23):
 * "es la misma persona" (link_existing), "crear nuevo atleta" (create_new),
 * "ignorar por ahora" (ignore).
 */
class ResolveAthleteIdentityConflict
{
    public function __construct(
        private readonly CreateAthlete $createAthlete,
        private readonly LinkParticipantToAthlete $linkParticipant,
    ) {}

    /**
     * @param  'link_existing'|'create_new'|'ignore'  $resolution
     */
    public function handle(AthleteIdentityConflict $conflict, string $resolution, User $actor, ?Athlete $chosenAthlete = null): AthleteIdentityConflict
    {
        return DB::transaction(function () use ($conflict, $resolution, $actor, $chosenAthlete) {
            $athlete = match ($resolution) {
                'link_existing' => $chosenAthlete ?? $conflict->candidateAthlete
                    ?? throw ValidationException::withMessages(['athlete_id' => 'Selecciona el atleta correcto.']),
                'create_new' => $this->createAthlete->handle(AthleteIdentityCandidateData::fromArray($conflict->incoming_data)),
                'ignore' => null,
                default => throw ValidationException::withMessages(['resolution' => 'Resolución inválida.']),
            };

            if ($athlete !== null && $conflict->eventParticipant !== null) {
                $this->linkParticipant->handle($conflict->eventParticipant, $athlete);
            }

            $conflict->update([
                'status' => $resolution === 'ignore' ? AthleteIdentityConflictStatus::Ignored : AthleteIdentityConflictStatus::Resolved,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
                'resolution' => $resolution,
            ]);

            activity()
                ->causedBy($actor)
                ->performedOn($conflict)
                ->withProperties(['resolution' => $resolution, 'athlete_id' => $athlete?->id])
                ->log("{$actor->name} resolvió un conflicto de identidad de atleta ({$resolution}).");

            return $conflict->fresh();
        });
    }
}
