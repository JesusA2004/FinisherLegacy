<?php

namespace App\Models;

use App\Enums\ProductionDeviceStatus;
use Database\Factories\ProductionDeviceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A paired Finisher Event Desktop instance (or simulator) — identity only,
 * never a credential holder (its Sanctum token lives in
 * `personal_access_tokens`, morphed to this model). Extends the same
 * `Authenticatable` base User does so `auth:sanctum` can resolve a device
 * token to an instance of this class; App\Http\Middleware\
 * EnsureProductionDeviceToken is what stops a device token from being
 * treated as a User (or vice versa) on the wrong routes — see
 * docs/adr/0002-device-production-api.md.
 */
#[Fillable([
    'uuid', 'name', 'station_code', 'machine_profile_id', 'event_edition_id',
    'status', 'last_seen_at', 'app_version', 'capabilities', 'metadata',
])]
class ProductionDevice extends Authenticatable
{
    /** @use HasFactory<ProductionDeviceFactory> */
    use HasApiTokens, HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'status' => ProductionDeviceStatus::class,
            'last_seen_at' => 'datetime',
            'capabilities' => 'array',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<MachineProfile, $this> */
    public function machineProfile(): BelongsTo
    {
        return $this->belongsTo(MachineProfile::class);
    }

    /** @return BelongsTo<EventEdition, $this> */
    public function eventEdition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class);
    }

    /** @return HasMany<ProductionJob, $this> */
    public function claimedJobs(): HasMany
    {
        return $this->hasMany(ProductionJob::class);
    }

    /**
     * Derived, never persisted — see App\Enums\ProductionDeviceStatus.
     */
    public function isOnline(): bool
    {
        if ($this->status !== ProductionDeviceStatus::Active || $this->last_seen_at === null) {
            return false;
        }

        /** @var Carbon $lastSeenAt */
        $lastSeenAt = $this->last_seen_at;

        return $lastSeenAt->diffInSeconds(now()) <= (int) config('finisher.device_online_timeout_seconds', 90);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
