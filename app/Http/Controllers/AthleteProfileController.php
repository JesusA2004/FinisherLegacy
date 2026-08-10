<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAthleteProfileRequest;
use App\Models\Sport;
use App\Services\AthleteProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AthleteProfileController extends Controller
{
    public function __construct(private readonly AthleteProfileService $profiles) {}

    public function edit(Request $request): Response
    {
        $profile = $request->user()->athleteProfile;

        return Inertia::render('dashboard/profile/Edit', [
            'profile' => $profile ? [
                'username' => $profile->username,
                'bio' => $profile->bio,
                'city' => $profile->city,
                'state' => $profile->state,
                'country' => $profile->country,
                'main_sport_id' => $profile->main_sport_id,
                'profile_visibility' => $profile->profile_visibility->value,
                'profile_photo_url' => $profile->profile_photo_path
                    ? Storage::disk('public')->url($profile->profile_photo_path)
                    : null,
                'cover_photo_url' => $profile->cover_photo_path
                    ? Storage::disk('public')->url($profile->cover_photo_path)
                    : null,
            ] : null,
            'sports' => Sport::query()->where('active', true)->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateAthleteProfileRequest $request): RedirectResponse
    {
        $this->profiles->update(
            $request->user(),
            $request->safe()->except(['profile_photo', 'cover_photo']),
            $request->file('profile_photo'),
            $request->file('cover_photo'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tu Legacy Profile fue actualizado.']);

        return to_route('dashboard.profile.edit');
    }
}
