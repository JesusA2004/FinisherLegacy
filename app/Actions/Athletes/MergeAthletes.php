<?php

namespace App\Actions\Athletes;

use App\Enums\AthleteIdentityStatus;
use App\Exceptions\Athletes\AthleteMergeUserConflictException;
use App\Exceptions\Athletes\ExternalIdentityConflictException;
use App\Models\Athlete;
use App\Models\AthleteExternalIdentity;
use App\Models\EventParticipant;
use App\Models\Medal;
use App\Models\Plate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Only reachable from manual conflict resolution — never called by the
 * matcher itself except at absolute certainty (i.e. never automatically;
 * see docs/adr/0004-athlete-canonical-identity.md §Merge). `source` is
 * retired (`identity_status = merged`, `merged_into_athlete_id` set), never
 * hard-deleted — every historical FK stays valid.
 */
class MergeAthletes
{
    public function handle(Athlete $source, Athlete $target, User $actor, string $reason): Athlete
    {
        if ($source->id === $target->id) {
            throw ValidationException::withMessages(['target' => 'No se puede fusionar un atleta consigo mismo.']);
        }

        return DB::transaction(function () use ($source, $target, $actor, $reason) {
            /** @var Athlete $source */
            $source = Athlete::whereKey($source->id)->lockForUpdate()->firstOrFail();
            /** @var Athlete $target */
            $target = Athlete::whereKey($target->id)->lockForUpdate()->firstOrFail();

            if ($source->user_id !== null && $target->user_id !== null && $source->user_id !== $target->user_id) {
                throw new AthleteMergeUserConflictException;
            }

            EventParticipant::where('athlete_id', $source->id)->update(['athlete_id' => $target->id]);
            Plate::where('athlete_id', $source->id)->update(['athlete_id' => $target->id]);
            Medal::where('athlete_id', $source->id)->update(['athlete_id' => $target->id]);

            foreach (AthleteExternalIdentity::where('athlete_id', $source->id)->get() as $identity) {
                $conflict = AthleteExternalIdentity::where('athlete_id', $target->id)
                    ->where('provider', $identity->provider)
                    ->where('provider_connection_id', $identity->provider_connection_id)
                    ->where('external_subject_id', $identity->external_subject_id)
                    ->exists();

                if ($conflict) {
                    throw new ExternalIdentityConflictException;
                }

                $identity->update(['athlete_id' => $target->id]);
            }

            $movedUserId = null;

            if ($source->user_id !== null && $target->user_id === null) {
                $movedUserId = $source->user_id;
            }

            // Source's user_id must be cleared BEFORE target's is set — both
            // columns share a unique index, so briefly holding the same
            // value on two rows (even mid-transaction) trips the constraint.
            $source->update([
                'merged_into_athlete_id' => $target->id,
                'identity_status' => AthleteIdentityStatus::Merged,
                'user_id' => null,
            ]);

            if ($movedUserId !== null) {
                $target->update(['user_id' => $movedUserId]);
            }

            activity()
                ->causedBy($actor)
                ->performedOn($target)
                ->withProperties(['source_athlete_id' => $source->id, 'reason' => $reason, 'moved_user_id' => $movedUserId])
                ->log("{$actor->name} fusionó el atleta #{$source->id} dentro del atleta #{$target->id}.");

            return $target->fresh();
        });
    }
}
