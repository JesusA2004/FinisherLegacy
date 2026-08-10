<?php

namespace App\Services;

use App\Enums\MedalImageType;
use App\Models\Medal;
use App\Models\MedalImage;
use Illuminate\Http\UploadedFile;

class MedalImageService
{
    public function __construct(private readonly ImageProcessingService $images) {}

    public function store(UploadedFile $file, Medal $medal, MedalImageType $type, int $sortOrder = 0): MedalImage
    {
        $processed = $this->images->process($file, "medals/{$medal->id}");

        return $medal->images()->create([
            'type' => $type,
            'original_path' => $processed['original_path'],
            'optimized_path' => $processed['display_path'],
            'thumbnail_path' => $processed['thumbnail_path'],
            'mime_type' => $processed['mime_type'],
            'file_size' => $processed['file_size'],
            'width' => $processed['width'],
            'height' => $processed['height'],
            'sort_order' => $sortOrder,
        ]);
    }

    public function delete(MedalImage $image): void
    {
        $this->images->delete([$image->original_path, $image->optimized_path, $image->thumbnail_path]);

        $image->delete();
    }
}
