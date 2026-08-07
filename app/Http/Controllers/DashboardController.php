<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $user->loadMissing(['legacyId', 'athleteProfile']);

        return Inertia::render('Dashboard', [
            'legacyId' => $user->legacyId?->code,
            'profile' => $user->athleteProfile ? [
                'username' => $user->athleteProfile->username,
                'profile_visibility' => $user->athleteProfile->profile_visibility->value,
            ] : null,
            'stats' => [
                'medals' => $user->medals()->count(),
                'events' => $user->eventParticipations()->count(),
                'plates' => $user->plates()->count(),
                'legacyCodes' => $user->legacyCodes()->count(),
            ],
        ]);
    }
}
