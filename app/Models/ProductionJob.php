<?php

namespace App\Models;

use App\Enums\ProductionJobStatus;
use Database\Factories\ProductionJobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'plate_id', 'event_edition_id', 'priority', 'status', 'assigned_user_id',
    'queued_at', 'started_at', 'completed_at', 'attempts', 'error_message',
])]
class ProductionJob extends Model
{
    /** @use HasFactory<ProductionJobFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'status' => ProductionJobStatus::class,
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Plate, $this> */
    public function plate(): BelongsTo
    {
        return $this->belongsTo(Plate::class);
    }

    /** @return BelongsTo<EventEdition, $this> */
    public function eventEdition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
