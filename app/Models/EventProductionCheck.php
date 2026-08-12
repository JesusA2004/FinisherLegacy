<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_edition_id', 'qr_tested_at', 'qr_tested_by', 'notes'])]
class EventProductionCheck extends Model
{
    protected function casts(): array
    {
        return [
            'qr_tested_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<EventEdition, $this> */
    public function eventEdition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class);
    }

    /** @return BelongsTo<User, $this> */
    public function qrTestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qr_tested_by');
    }
}
