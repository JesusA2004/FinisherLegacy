<?php

namespace App\Models;

use App\Enums\ProviderConnectionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A configured connection to an external event/timing provider —
 * credentials + bookkeeping only. The provider *implementation* (how to
 * talk to it) is code, resolved by `provider_key` through
 * App\Services\Integrations\EventProviderRegistry. See
 * docs/adr/0005-unified-event-ingestion.md.
 */
#[Fillable(['uuid', 'provider_key', 'name', 'base_url', 'credentials', 'settings', 'status', 'last_tested_at', 'last_successful_sync_at'])]
class ProviderConnection extends Model
{
    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted',
            'settings' => 'encrypted:array',
            'status' => ProviderConnectionStatus::class,
            'last_tested_at' => 'datetime',
            'last_successful_sync_at' => 'datetime',
        ];
    }

    /** @return HasMany<ExternalEventMapping, $this> */
    public function eventMappings(): HasMany
    {
        return $this->hasMany(ExternalEventMapping::class);
    }

    /** @return HasMany<ExternalSyncRun, $this> */
    public function syncRuns(): HasMany
    {
        return $this->hasMany(ExternalSyncRun::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsArray(): array
    {
        return $this->settings ?? [];
    }
}
