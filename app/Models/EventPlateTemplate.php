<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_edition_id', 'event_race_id', 'plate_template_version_id', 'is_default', 'active'])]
class EventPlateTemplate extends Model
{
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /** @return BelongsTo<EventEdition, $this> */
    public function eventEdition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class);
    }

    /** @return BelongsTo<EventRace, $this> */
    public function eventRace(): BelongsTo
    {
        return $this->belongsTo(EventRace::class);
    }

    /** @return BelongsTo<PlateTemplateVersion, $this> */
    public function plateTemplateVersion(): BelongsTo
    {
        return $this->belongsTo(PlateTemplateVersion::class);
    }
}
