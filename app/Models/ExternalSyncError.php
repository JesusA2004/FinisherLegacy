<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sync_run_id', 'entity_type', 'external_id', 'code', 'message', 'context'])]
class ExternalSyncError extends Model
{
    public $timestamps = false;

    protected $attributes = [];

    protected static function booted(): void
    {
        static::creating(function (self $error) {
            $error->created_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ExternalSyncRun, $this> */
    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(ExternalSyncRun::class, 'sync_run_id');
    }
}
