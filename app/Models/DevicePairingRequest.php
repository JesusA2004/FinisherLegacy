<?php

namespace App\Models;

use App\Enums\DevicePairingStatus;
use Database\Factories\DevicePairingRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single pairing handshake attempt. `code` is a human correlator shown to
 * a Super Admin, never a credential; `poll_token_hash` (SHA-256, mirroring
 * how Sanctum itself never stores a plaintext token) is what actually gates
 * the one-time token delivery in App\Actions\Devices\ConfirmDevicePairing.
 * See docs/adr/0002-device-production-api.md.
 */
#[Fillable([
    'code', 'poll_token_hash', 'status', 'requested_name', 'requested_station_code',
    'requested_app_version', 'requested_capabilities', 'production_device_id',
    'machine_profile_id', 'event_edition_id', 'approved_by', 'approved_at',
    'completed_at', 'expires_at',
])]
class DevicePairingRequest extends Model
{
    /** @use HasFactory<DevicePairingRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => DevicePairingStatus::class,
            'requested_capabilities' => 'array',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProductionDevice, $this> */
    public function productionDevice(): BelongsTo
    {
        return $this->belongsTo(ProductionDevice::class);
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

    /** @return BelongsTo<User, $this> */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
