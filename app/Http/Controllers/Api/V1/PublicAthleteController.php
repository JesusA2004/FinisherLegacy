<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PublicMedalResource;
use App\Models\AthleteProfile;
use App\Services\PublicAthleteProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicAthleteController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly PublicAthleteProfileService $profiles) {}

    public function show(Request $request, AthleteProfile $athleteProfile): JsonResponse
    {
        $athleteProfile->load(['user', 'mainSport']);

        abort_unless($this->profiles->isVisibleTo($athleteProfile, $request->user()), 404);

        $medals = $this->profiles->publicMedals($athleteProfile);

        return $this->respond([
            'profile' => $this->profiles->profilePayload($athleteProfile),
            'stats' => $this->profiles->statsPayload($athleteProfile, $medals),
            'medals' => PublicMedalResource::collection($medals),
        ]);
    }
}
