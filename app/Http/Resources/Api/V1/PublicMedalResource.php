<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Medal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Medal */
class PublicMedalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $frontImage = $this->images->firstWhere('type', 'front');

        return [
            'id' => $this->uuid,
            'title' => $this->title,
            'distance_label' => $this->distance_label,
            'event_date' => $this->event_date?->toDateString(),
            'thumbnail_url' => $frontImage?->thumbnail_path
                ? Storage::disk('public')->url($frontImage->thumbnail_path)
                : null,
        ];
    }
}
