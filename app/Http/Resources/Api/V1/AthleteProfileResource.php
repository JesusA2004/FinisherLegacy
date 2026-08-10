<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AthleteProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin AthleteProfile */
class AthleteProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'username' => $this->username,
            'bio' => $this->bio,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'main_sport_id' => $this->main_sport_id,
            'sport' => $this->whenLoaded('mainSport', fn () => $this->mainSport?->name),
            'profile_visibility' => $this->profile_visibility->value,
            'profile_photo_url' => $this->profile_photo_path
                ? Storage::disk('public')->url($this->profile_photo_path)
                : null,
            'cover_photo_url' => $this->cover_photo_path
                ? Storage::disk('public')->url($this->cover_photo_path)
                : null,
        ];
    }
}
