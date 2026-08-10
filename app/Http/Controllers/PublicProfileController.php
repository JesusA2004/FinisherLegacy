<?php

namespace App\Http\Controllers;

use App\Models\AthleteProfile;
use App\Services\PublicAthleteProfileService;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    public function __construct(private readonly PublicAthleteProfileService $profiles) {}

    public function show(AthleteProfile $athleteProfile): Response
    {
        $athleteProfile->load(['user', 'mainSport']);

        $isOwner = auth()->check() && auth()->id() === $athleteProfile->user_id;

        if (! $this->profiles->isVisibleTo($athleteProfile, auth()->user())) {
            return Inertia::render('profile/Private', [
                'username' => $athleteProfile->username,
            ]);
        }

        $medals = $this->profiles->publicMedals($athleteProfile);

        return Inertia::render('profile/Show', [
            'isOwner' => $isOwner,
            'profile' => $this->profiles->profilePayload($athleteProfile),
            'stats' => $this->profiles->statsPayload($athleteProfile, $medals),
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
