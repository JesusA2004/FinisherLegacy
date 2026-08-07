<?php

namespace App\Models;

use App\Enums\EditionStatus;
use App\Enums\OperationMode;
use App\Enums\ResultsStatus;
use Database\Factories\EventEditionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'event_id', 'name', 'year', 'event_date', 'city', 'state', 'country', 'timezone',
    'registration_open_at', 'registration_close_at', 'operation_mode', 'status', 'results_status',
])]
class EventEdition extends Model
{
    /** @use HasFactory<EventEditionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'registration_open_at' => 'datetime',
            'registration_close_at' => 'datetime',
            'operation_mode' => OperationMode::class,
            'status' => EditionStatus::class,
            'results_status' => ResultsStatus::class,
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return HasMany<EventRace, $this> */
    public function races(): HasMany
    {
        return $this->hasMany(EventRace::class);
    }

    /** @return HasMany<EventParticipant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    /** @return HasMany<EventPreregistration, $this> */
    public function preregistrations(): HasMany
    {
        return $this->hasMany(EventPreregistration::class);
    }

    /** @return HasMany<EventImport, $this> */
    public function imports(): HasMany
    {
        return $this->hasMany(EventImport::class);
    }

    /** @return HasMany<EventStaffAssignment, $this> */
    public function staffAssignments(): HasMany
    {
        return $this->hasMany(EventStaffAssignment::class);
    }

    /** @return HasMany<Plate, $this> */
    public function plates(): HasMany
    {
        return $this->hasMany(Plate::class);
    }

    /** @return HasMany<ProductionJob, $this> */
    public function productionJobs(): HasMany
    {
        return $this->hasMany(ProductionJob::class);
    }

    /** @return HasMany<EventIncident, $this> */
    public function incidents(): HasMany
    {
        return $this->hasMany(EventIncident::class);
    }

    /** @return HasMany<Medal, $this> */
    public function medals(): HasMany
    {
        return $this->hasMany(Medal::class);
    }
}
