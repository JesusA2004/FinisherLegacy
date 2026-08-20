<?php

namespace App\Actions\Integrations;

use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventEdition;
use App\Models\EventRace;
use App\Models\ExternalEventMapping;
use App\Models\ProviderConnection;
use App\Models\Sport;
use App\Support\Integrations\ExternalEventData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The only place an Event/EventEdition is created from provider data — no
 * controller ever calls Event::create() directly for this flow (docs/adr/0005
 * §67). Deliberately asks for a Sport rather than inventing one: `sport_id`
 * is NOT NULL on `events` and guessing wrong would be worse than asking
 * once at link time. `organizer_id` is left null — Fase 1 never fabricates
 * a placeholder organizer (§68).
 */
class CreateEventFromExternalData
{
    public function __construct(private readonly LinkExternalEvent $linkEvent) {}

    public function handle(ExternalEventData $data, ProviderConnection $connection, Sport $sport): ExternalEventMapping
    {
        return DB::transaction(function () use ($data, $connection, $sport) {
            $event = Event::create([
                'sport_id' => $sport->id,
                'name' => $data->name,
                'slug' => Str::slug($data->name).'-'.Str::lower(Str::random(6)),
                'status' => EventStatus::Draft,
            ]);

            $edition = EventEdition::create([
                'event_id' => $event->id,
                'name' => (string) ($data->year ?? now()->year),
                'year' => $data->year ?? (int) now()->year,
                'event_date' => $data->date ?? now()->toDateString(),
                'city' => $data->city ?? '—',
                'state' => $data->state,
                'country' => $data->country ?? 'MX',
                'timezone' => $data->timezone ?? 'America/Mexico_City',
                'status' => EditionStatus::Draft,
            ]);

            foreach ($data->races as $race) {
                EventRace::create([
                    'event_edition_id' => $edition->id,
                    'name' => $race->name,
                    'distance_value' => $race->distanceValue,
                    'distance_unit' => $race->distanceUnit,
                    'race_type' => $race->raceType,
                    'active' => true,
                ]);
            }

            return $this->linkEvent->handle($connection, $data->externalId, $edition);
        });
    }
}
