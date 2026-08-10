<?php

namespace App\Models;

use App\Enums\MedalImageType;
use Database\Factories\MedalImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'medal_id', 'type', 'original_path', 'optimized_path', 'thumbnail_path',
    'mime_type', 'file_size', 'width', 'height', 'sort_order',
])]
class MedalImage extends Model
{
    /** @use HasFactory<MedalImageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => MedalImageType::class,
        ];
    }

    /** @return BelongsTo<Medal, $this> */
    public function medal(): BelongsTo
    {
        return $this->belongsTo(Medal::class);
    }
}
