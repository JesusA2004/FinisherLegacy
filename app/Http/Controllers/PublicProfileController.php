<?php

namespace App\Http\Controllers;

use App\Models\AthleteProfile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    public function show(AthleteProfile $athleteProfile): Response
    {
        $athleteProfile->load(['user', 'mainSport']);

        $isOwner = auth()->check() && auth()->id() === $athleteProfile->user_id;
        $isPrivate = $athleteProfile->profile_visibility->value === 'private';

        if ($isPrivate && ! $isOwner) {
            return Inertia::render('profile/Private', [
                'username' => $athleteProfile->username,
            ]);
        }

        $medals = $athleteProfile->user->medals()
            ->where('visibility', 'public')
            ->with('images')
            ->latest()
            ->get();

        return Inertia::render('profile/Show', [
            'isOwner' => $isOwner,
            'profile' => [
                'name' => $athleteProfile->user->name,
                'username' => $athleteProfile->username,
                'bio' => $athleteProfile->bio,
                'city' => $athleteProfile->city,
                'country' => $athleteProfile->country,
                'sport' => $athleteProfile->mainSport?->name,
                'photo_url' => $athleteProfile->profile_photo_path
                    ? Storage::disk('public')->url($athleteProfile->profile_photo_path)
                    : null,
                'cover_url' => $athleteProfile->cover_photo_path
                    ? Storage::disk('public')->url($athleteProfile->cover_photo_path)
                    : null,
            ],
            'stats' => [
                'medals' => $medals->count(),
                'events' => $athleteProfile->user->eventParticipations()->count(),
            ],
            'medals' => $medals->map(function ($medal) {
                $frontImage = $medal->images->firstWhere('type', 'front');

                return [
                    'id' => $medal->id,
                    'title' => $medal->title,
                    'distance_label' => $medal->distance_label,
                    'event_date' => $medal->event_date?->toDateString(),
                    'thumbnail_url' => $frontImage?->thumbnail_path
                        ? Storage::disk('public')->url($frontImage->thumbnail_path)
                        : null,
                ];
            }),
        ]);
    }
}
