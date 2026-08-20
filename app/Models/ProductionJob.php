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
    'front_engraved_at', 'front_engraved_by', 'back_engraved_at', 'back_engraved_by',
    'qr_verified_at', 'qr_verified_by',
    'production_device_id', 'claimed_at', 'lease_expires_at',
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
            'front_engraved_at' => 'datetime',
            'back_engraved_at' => 'datetime',
            'qr_verified_at' => 'datetime',
            'claimed_at' => 'datetime',
            'lease_expires_at' => 'datetime',
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

    /** @return BelongsTo<User, $this> */
    public function frontEngravedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'front_engraved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function backEngravedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'back_engraved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function qrVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qr_verified_by');
    }

    /** @return BelongsTo<ProductionDevice, $this> */
    public function productionDevice(): BelongsTo
    {
        return $this->belongsTo(ProductionDevice::class);
    }

    /**
     * True once a device may claim this job — either it's unclaimed, or its
     * lease has lapsed. Does NOT check `status`; callers filter by
     * ProductionJobStatus::Queued separately (see
     * App\Services\Devices\ProductionJobClaimService).
     */
    public function hasClaimableLease(): bool
    {
        return $this->production_device_id === null
            || ($this->lease_expires_at !== null && $this->lease_expires_at->isPast());
    }

    public function checklistComplete(): bool
    {
        return $this->front_engraved_at !== null
            && $this->back_engraved_at !== null
            && $this->qr_verified_at !== null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
