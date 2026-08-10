<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Medal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Medal */
class MedalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $frontImage = $this->images->firstWhere('type', 'front');
        $backImage = $this->images->firstWhere('type', 'back');
        $galleryImages = $this->images->where('type', 'gallery')->sortBy('sort_order')->values();

        return [
            'id' => $this->uuid,
            'title' => $this->title,
            'event_name_manual' => $this->event_name_manual,
            'event_date' => $this->event_date?->toDateString(),
            'distance_label' => $this->distance_label,
            'official_time' => $this->official_time,
            'pace' => $this->pace,
            'city' => $this->city,
            'country' => $this->country,
            'story' => $this->story,
            'visibility' => $this->visibility->value,
            'is_official' => $this->event_participant_id !== null,
            'event_name' => $this->whenLoaded('eventEdition', fn () => $this->eventEdition?->event?->name),
            'race_name' => $this->whenLoaded('eventRace', fn () => $this->eventRace?->name),
            'front_image_url' => $frontImage?->optimized_path
                ? Storage::disk('public')->url($frontImage->optimized_path)
                : null,
            'back_image_url' => $backImage?->optimized_path
                ? Storage::disk('public')->url($backImage->optimized_path)
                : null,
            'gallery_images' => $galleryImages->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->optimized_path ? Storage::disk('public')->url($image->optimized_path) : null,
            ])->values(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
