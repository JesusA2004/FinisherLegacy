<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'width', 'height', 'configuration', 'preview_path', 'active'])]
class PlateTemplate extends Model
{
    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'active' => 'boolean',
        ];
    }

    /** @return HasMany<Plate, $this> */
    public function plates(): HasMany
    {
        return $this->hasMany(Plate::class);
    }
}
