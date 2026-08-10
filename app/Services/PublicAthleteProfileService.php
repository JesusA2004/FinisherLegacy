<?php

namespace App\Services;

use App\Models\AthleteProfile;
use App\Models\Medal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the payload for a public Legacy Profile page — shared by the web
 * /@{username} route and the /api/v1/athletes/{username} endpoint, so the
 * "who can see this profile" and "which medals count" rules only live here.
 */
class PublicAthleteProfileService
{
    public function isVisibleTo(AthleteProfile $profile, ?User $viewer): bool
    {
        if ($profile->profile_visibility->value === 'public') {
            return true;
        }

        return $viewer !== null && $viewer->id === $profile->user_id;
    }

    /**
     * @return Collection<int, Medal>
     */
    public function publicMedals(AthleteProfile $profile): Collection
    {
        return $profile->user->medals()
            ->where('visibility', 'public')
            ->with('images')
            ->latest()
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function profilePayload(AthleteProfile $profile): array
    {
        return [
            'name' => $profile->user->name,
            'username' => $profile->username,
            'bio' => $profile->bio,
            'city' => $profile->city,
            'country' => $profile->country,
            'sport' => $profile->mainSport?->name,
            'photo_url' => $profile->profile_photo_path
                ? Storage::disk('public')->url($profile->profile_photo_path)
                : null,
            'cover_url' => $profile->cover_photo_path
                ? Storage::disk('public')->url($profile->cover_photo_path)
                : null,
        ];
    }

    /**
     * @param  Collection<int, Medal>  $medals
     * @return array<string, mixed>
     */
    public function statsPayload(AthleteProfile $profile, Collection $medals): array
    {
        return [
            'medals' => $medals->count(),
            'events' => $profile->user->eventParticipations()->count(),
        ];
    }
}
