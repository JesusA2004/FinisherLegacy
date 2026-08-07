<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_import_id', 'row_number', 'raw_data', 'error_code', 'error_message'])]
class EventImportError extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
        ];
    }

    /** @return BelongsTo<EventImport, $this> */
    public function eventImport(): BelongsTo
    {
        return $this->belongsTo(EventImport::class);
    }
}
