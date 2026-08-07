<?php

namespace App\Services;

use App\Enums\MedalImageType;
use App\Models\Medal;
use App\Models\MedalImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageManagerInterface;

class MedalImageService
{
    private const MAX_WIDTH = 1600;

    private const THUMBNAIL_SIZE = 400;

    private ImageManagerInterface $manager;

    public function __construct()
    {
        $this->manager = ImageManager::usingDriver(Driver::class);
    }

    public function store(UploadedFile $file, Medal $medal, MedalImageType $type, int $sortOrder = 0): MedalImage
    {
        $disk = Storage::disk('public');
        $directory = "medals/{$medal->id}";
        $basename = (string) Str::uuid();

        $originalExtension = $file->getClientOriginalExtension() ?: 'jpg';
        $originalPath = "{$directory}/original/{$basename}.{$originalExtension}";
        $disk->putFileAs("{$directory}/original", $file, "{$basename}.{$originalExtension}");

        $image = $this->manager->decodePath($file->getRealPath());
        $width = $image->width();
        $height = $image->height();

        $image->scaleDown(width: self::MAX_WIDTH);
        $optimizedPath = "{$directory}/optimized/{$basename}.jpg";
        $disk->put($optimizedPath, (string) $image->encode(new JpegEncoder(quality: 82)));

        $image->cover(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);
        $thumbnailPath = "{$directory}/thumbnails/{$basename}.jpg";
        $disk->put($thumbnailPath, (string) $image->encode(new JpegEncoder(quality: 75)));

        return $medal->images()->create([
            'type' => $type,
            'original_path' => $originalPath,
            'optimized_path' => $optimizedPath,
            'thumbnail_path' => $thumbnailPath,
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'sort_order' => $sortOrder,
        ]);
    }

    public function delete(MedalImage $image): void
    {
        $disk = Storage::disk('public');

        foreach ([$image->original_path, $image->optimized_path, $image->thumbnail_path] as $path) {
            if ($path) {
                $disk->delete($path);
            }
        }

        $image->delete();
    }
}
