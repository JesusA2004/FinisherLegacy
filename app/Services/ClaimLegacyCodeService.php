<?php

namespace App\Services;

use App\Enums\LegacyCodeStatus;
use App\Exceptions\LegacyCodeClaimConflictException;
use App\Exceptions\LegacyCodeUnavailableException;
use App\Models\LegacyCode;
use App\Models\Medal;
use App\Models\Plate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Turns a scanned/visited Legacy Code into a permanent part of a Legacy
 * Profile. This is the single implementation for both the "quick plate"
 * (no participant, produced ahead of time) and "integrated" (participant +
 * result already known) paths — the difference lives entirely in what the
 * Plate snapshot already contains, never in this service's logic.
 *
 * Web and API controllers both call this — never duplicate the claim
 * transaction/lock/audit logic elsewhere.
 */
class ClaimLegacyCodeService
{
    private const UNAVAILABLE_STATUSES = [
        LegacyCodeStatus::Blocked,
        LegacyCodeStatus::Cancelled,
        LegacyCodeStatus::Replaced,
    ];

    /**
     * @return array{legacyCode: LegacyCode, medal: ?Medal, alreadyOwned: bool}
     */
    public function claim(string $code, User $user, ?string $ip = null): array
    {
        return DB::transaction(function () use ($code, $user, $ip) {
            /** @var LegacyCode $legacyCode */
            $legacyCode = LegacyCode::query()
                ->where('code', $code)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($legacyCode->status, self::UNAVAILABLE_STATUSES, true)) {
                throw new LegacyCodeUnavailableException;
            }

            if ($legacyCode->claimed_by_user_id === $user->id) {
                return [
                    'legacyCode' => $legacyCode,
                    'medal' => $legacyCode->medal_id ? Medal::find($legacyCode->medal_id) : null,
                    'alreadyOwned' => true,
                ];
            }

            if ($legacyCode->claimed_by_user_id !== null) {
                throw new LegacyCodeClaimConflictException;
            }

            $plate = $legacyCode->plate_id
                ? Plate::query()->lockForUpdate()->find($legacyCode->plate_id)
                : null;

            $medal = $plate ? $this->linkPlate($plate, $user) : null;

            $legacyCode->update([
                'user_id' => $user->id,
                'claimed_by_user_id' => $user->id,
                'claimed_at' => now(),
                'status' => LegacyCodeStatus::Claimed,
                'medal_id' => $medal?->id,
            ]);

            activity()
                ->causedBy($user)
                ->performedOn($legacyCode)
                ->withProperties(array_filter(['ip' => $ip]))
                ->log('legacy_code.claimed');

            return [
                'legacyCode' => $legacyCode->fresh(),
                'medal' => $medal,
                'alreadyOwned' => false,
            ];
        });
    }

    private function linkPlate(Plate $plate, User $user): ?Medal
    {
        $plate->update([
            'user_id' => $user->id,
            'linked_at' => now(),
        ]);

        if ($plate->medal_id) {
            return Medal::find($plate->medal_id);
        }

        $plate->loadMissing(['eventEdition.event', 'eventParticipant']);

        /** @var Medal $medal */
        $medal = $user->medals()->create([
            'event_id' => $plate->eventEdition?->event_id,
            'event_edition_id' => $plate->event_edition_id,
            'event_race_id' => $plate->eventParticipant?->event_race_id,
            'event_participant_id' => $plate->event_participant_id,
            'title' => $plate->event_name ?? 'Placa Finisher Legacy',
            'event_name_manual' => $plate->event_participant_id ? null : $plate->event_name,
            'event_date' => $plate->event_date,
            'distance_label' => $plate->race_name,
            'official_time' => $plate->official_time,
            'pace' => $plate->pace,
            'city' => $plate->eventEdition?->city,
            'country' => $plate->eventEdition?->country,
            'story' => null,
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $plate->update(['medal_id' => $medal->id]);

        return $medal;
    }
}
