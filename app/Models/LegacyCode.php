<?php

namespace App\Models;

use App\Enums\LegacyCodeStatus;
use Database\Factories\LegacyCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'code', 'uuid', 'plate_id', 'user_id', 'medal_id', 'status',
    'assigned_at', 'claimed_at', 'claimed_by_user_id', 'blocked_at',
])]
class LegacyCode extends Model
{
    /** @use HasFactory<LegacyCodeFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'status' => LegacyCodeStatus::class,
            'assigned_at' => 'datetime',
            'claimed_at' => 'datetime',
            'blocked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Plate, $this> */
    public function plate(): BelongsTo
    {
        return $this->belongsTo(Plate::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Medal, $this> */
    public function medal(): BelongsTo
    {
        return $this->belongsTo(Medal::class);
    }

    /** @return BelongsTo<User, $this> */
    public function claimedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
