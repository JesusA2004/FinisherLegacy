<?php

namespace App\Http\Controllers;

use App\Enums\LegacyCodeStatus;
use App\Models\LegacyCode;
use Inertia\Inertia;
use Inertia\Response;

class LegacyCodeController extends Controller
{
    public function show(string $code): Response
    {
        $legacyCode = LegacyCode::query()
            ->where('code', $code)
            ->with(['plate', 'user.athleteProfile.mainSport'])
            ->first();

        abort_unless($legacyCode !== null, 404);

        $unavailableStatuses = [LegacyCodeStatus::Blocked, LegacyCodeStatus::Cancelled, LegacyCodeStatus::Replaced];

        if (in_array($legacyCode->status, $unavailableStatuses, true)) {
            return Inertia::render('legacy-code/Show', [
                'code' => $legacyCode->code,
                'available' => false,
                'linked' => false,
                'plate' => null,
                'athlete' => null,
            ]);
        }

        $plate = $legacyCode->plate;
        $profile = $legacyCode->user?->athleteProfile;
        $showProfile = $profile && $profile->profile_visibility->value === 'public';

        return Inertia::render('legacy-code/Show', [
            'code' => $legacyCode->code,
            'available' => true,
            'linked' => $legacyCode->user_id !== null,
            'plate' => $plate ? [
                'athlete_name' => $plate->athlete_name,
                'event_name' => $plate->event_name,
                'race_name' => $plate->race_name,
                'official_time' => $plate->official_time,
                'pace' => $plate->pace,
                'event_date' => $plate->event_date?->toDateString(),
                'status' => $plate->status->value,
            ] : null,
            'athlete' => $showProfile ? [
                'username' => $profile->username,
                'city' => $profile->city,
                'sport' => $profile->mainSport?->name,
            ] : null,
        ]);
    }
}
