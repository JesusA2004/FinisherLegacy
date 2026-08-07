<?php

namespace App\Models;

use App\Enums\ReprintStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['plate_id', 'requested_by', 'reason', 'notes', 'approved_by', 'status'])]
class PlateReprint extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'status' => ReprintStatus::class,
        ];
    }

    /** @return BelongsTo<Plate, $this> */
    public function plate(): BelongsTo
    {
        return $this->belongsTo(Plate::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
