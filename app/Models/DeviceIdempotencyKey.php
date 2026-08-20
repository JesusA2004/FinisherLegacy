<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A stored replay for one `Idempotency-Key` on one device write route.
 * See App\Http\Middleware\EnsureIdempotencyKey.
 */
#[Fillable(['production_device_id', 'route', 'key', 'response_status', 'response_body'])]
class DeviceIdempotencyKey extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProductionDevice, $this> */
    public function productionDevice(): BelongsTo
    {
        return $this->belongsTo(ProductionDevice::class);
    }
}
