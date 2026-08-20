<?php

namespace App\Models;

use App\Contracts\ProductionActor;
use App\Enums\ProductionJobStatus;
use Database\Factories\ProductionJobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'plate_id', 'event_edition_id', 'machine_profile_id', 'priority', 'status', 'assigned_user_id',
    'queued_at', 'started_at', 'completed_at', 'attempts', 'error_message', 'error_code',
    'front_engraved_at', 'front_engraved_by', 'back_engraved_at', 'back_engraved_by',
    'qr_verified_at', 'qr_verified_by',
    'production_device_id', 'claimed_at', 'lease_expires_at',
    'preparation_started_at', 'front_started_at', 'flip_confirmed_at', 'back_started_at',
    'ready_at', 'delivered_at', 'qr_decoded_value',
    'front_actor_type', 'front_actor_id', 'back_actor_type', 'back_actor_id',
    'flip_actor_type', 'flip_actor_id', 'qr_actor_type', 'qr_actor_id',
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
            'preparation_started_at' => 'datetime',
            'front_started_at' => 'datetime',
            'flip_confirmed_at' => 'datetime',
            'back_started_at' => 'datetime',
            'ready_at' => 'datetime',
            'delivered_at' => 'datetime',
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

    /** @return BelongsTo<MachineProfile, $this> */
    public function machineProfile(): BelongsTo
    {
        return $this->belongsTo(MachineProfile::class);
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

    /** @return HasOne<ProductionArtifact, $this> */
    public function artifact(): HasOne
    {
        return $this->hasOne(ProductionArtifact::class);
    }

    /** @return MorphTo<Model, $this> */
    public function frontActor(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function backActor(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function flipActor(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function qrActor(): MorphTo
    {
        return $this->morphTo();
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

    /**
     * A safe-to-reclaim job: nothing physical has happened to it yet.
     * Assigned/Preparing always qualify (front never started while in
     * those states, by construction of the state machine) — engraving
     * states never do, on purpose. See docs/adr/0003 §Lease.
     */
    public function isSafeToRelease(): bool
    {
        return in_array($this->status, [ProductionJobStatus::Assigned, ProductionJobStatus::Preparing], true);
    }

    public function checklistComplete(): bool
    {
        return $this->front_engraved_at !== null
            && $this->back_engraved_at !== null
            && $this->qr_verified_at !== null;
    }

    /**
     * Machine-readable next step for a Device API client — never make a
     * desktop guess a UI action from a status string. Null once the job
     * reached a terminal state (or hasn't been claimed yet — "claim" isn't
     * represented here, it's not a state the job itself is "in").
     *
     * @return 'prepare'|'start_front'|'complete_front'|'confirm_flip'|'start_back'|'complete_back'|'verify_qr'|'deliver'|null
     */
    public function nextAction(): ?string
    {
        return match ($this->status) {
            ProductionJobStatus::Assigned => 'prepare',
            ProductionJobStatus::Preparing => 'start_front',
            ProductionJobStatus::EngravingFront => 'complete_front',
            ProductionJobStatus::AwaitingFlip => $this->flip_confirmed_at === null ? 'confirm_flip' : 'start_back',
            ProductionJobStatus::EngravingBack => 'complete_back',
            ProductionJobStatus::VerifyingQr => 'verify_qr',
            ProductionJobStatus::Ready => 'deliver',
            default => null,
        };
    }

    /**
     * Attributes to merge into an `update()` call to record who performed
     * one of the four state-machine-gated events — never mutates the model
     * directly, so callers combine it with the status/timestamp change in
     * one query instead of two.
     *
     * @param  'front'|'back'|'flip'|'qr'  $event
     * @return array<string, mixed>
     */
    public static function actorAttributes(string $event, ProductionActor $actor): array
    {
        abort_unless($actor instanceof Model, 500, 'Un actor de producción debe ser un modelo Eloquent.');

        return [
            "{$event}_actor_type" => $actor->getMorphClass(),
            "{$event}_actor_id" => $actor->getKey(),
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
