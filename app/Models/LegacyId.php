<?php

namespace App\Models;

use App\Enums\LegacyIdStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'code', 'status', 'issued_at'])]
class LegacyId extends Model
{
    protected function casts(): array
    {
        return [
            'status' => LegacyIdStatus::class,
            'issued_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
