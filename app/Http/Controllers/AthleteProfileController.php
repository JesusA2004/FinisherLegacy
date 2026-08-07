<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAthleteProfileRequest;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AthleteProfileController extends Controller
{
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
        $user = $request->user();
        $profile = $user->athleteProfile ?: $user->athleteProfile()->make();

        $profile->fill($request->safe()->except(['profile_photo', 'cover_photo']));

        if ($request->hasFile('profile_photo')) {
            if ($profile->profile_photo_path) {
                Storage::disk('public')->delete($profile->profile_photo_path);
            }

            $path = $request->file('profile_photo')->store('profiles', 'public');
            abort_if($path === false, 500, 'No se pudo guardar la foto de perfil.');
            $profile->profile_photo_path = $path;
        }

        if ($request->hasFile('cover_photo')) {
            if ($profile->cover_photo_path) {
                Storage::disk('public')->delete($profile->cover_photo_path);
            }

            $path = $request->file('cover_photo')->store('profiles/covers', 'public');
            abort_if($path === false, 500, 'No se pudo guardar la portada.');
            $profile->cover_photo_path = $path;
        }

        $profile->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tu Legacy Profile fue actualizado.']);

        return to_route('dashboard.profile.edit');
    }
}
