<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAthleteProfileRequest;
use App\Http\Resources\Api\V1\AthleteProfileResource;
use App\Services\AthleteProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly AthleteProfileService $profiles) {}

    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->athleteProfile;

        return $this->respond($profile ? new AthleteProfileResource($profile->loadMissing('mainSport')) : null);
    }

    public function update(UpdateAthleteProfileRequest $request): JsonResponse
    {
        $profile = $this->profiles->update(
            $request->user(),
            $request->safe()->except(['profile_photo', 'cover_photo']),
            $request->file('profile_photo'),
            $request->file('cover_photo'),
        );

        return $this->respond(
            new AthleteProfileResource($profile->loadMissing('mainSport')),
            'Tu Legacy Profile fue actualizado.',
        );
    }
}
