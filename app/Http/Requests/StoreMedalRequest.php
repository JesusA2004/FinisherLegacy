<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mimes = implode(',', config('finisher.image.mimes'));
        $maxFront = config('finisher.medal.front.max_kb');
        $maxBack = config('finisher.medal.back.max_kb');
        $maxGallery = config('finisher.medal.gallery.max_kb');
        $maxGalleryFiles = config('finisher.medal.gallery.max_files');

        return [
            'origin' => ['required', 'string', 'in:registered,manual'],
            'event_id' => ['required_if:origin,registered', 'nullable', 'integer', 'exists:events,id'],
            'event_edition_id' => ['required_if:origin,registered', 'nullable', 'integer', 'exists:event_editions,id'],
            'event_race_id' => ['required_if:origin,registered', 'nullable', 'integer', 'exists:event_races,id'],
            'event_name_manual' => ['required_if:origin,manual', 'nullable', 'string', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'distance_label' => ['nullable', 'string', 'max:50'],
            'official_time' => ['nullable', 'string', 'max:20'],
            'pace' => ['nullable', 'string', 'max:20'],
            'front_image' => ['required', 'image', "mimes:{$mimes}", "max:{$maxFront}"],
            'back_image' => ['nullable', 'image', "mimes:{$mimes}", "max:{$maxBack}"],
            'gallery_images' => ['nullable', 'array', "max:{$maxGalleryFiles}"],
            'gallery_images.*' => ['image', "mimes:{$mimes}", "max:{$maxGallery}"],
            'story' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', 'string', 'in:public,private'],
        ];
    }
}
