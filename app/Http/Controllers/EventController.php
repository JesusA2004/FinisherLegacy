<?php

namespace App\Http\Controllers;

use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Http\Resources\EventEditionCardResource;
use App\Models\Event;
use App\Models\EventEdition;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $sportSlug = $request->query('sport');
        $phase = $request->query('status');

        $query = EventEdition::query()
            ->whereHas('event', fn ($q) => $q->where('status', EventStatus::Published))
            ->where('status', EditionStatus::Published)
            ->with(['event.sport', 'races']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('city', 'like', "%{$search}%")
                    ->orWhereHas('event', fn ($eq) => $eq->where('name', 'like', "%{$search}%"));
            });
        }

        if (is_string($sportSlug) && $sportSlug !== '') {
            $query->whereHas('event.sport', fn ($q) => $q->where('slug', $sportSlug));
        }

        if (in_array($phase, ['upcoming', 'ongoing', 'finished'], true)) {
            $today = now()->toDateString();
            match ($phase) {
                'upcoming' => $query->whereDate('event_date', '>', $today),
                'ongoing' => $query->whereDate('event_date', '=', $today),
                'finished' => $query->whereDate('event_date', '<', $today),
            };
        }

        $editions = $query->orderBy('event_date')->paginate(9)->withQueryString();
        $editions->through(fn (EventEdition $edition) => (new EventEditionCardResource($edition))->resolve());

        return Inertia::render('events/Index', [
            'editions' => $editions,
            'sports' => Sport::query()->where('active', true)->orderBy('sort_order')->get(['id', 'name', 'slug']),
            'filters' => [
                'q' => $search,
                'sport' => $sportSlug,
                'status' => $phase,
            ],
        ]);
    }

    public function show(Event $event): Response
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $event->load(['organizer', 'sport']);
        $edition = $this->currentEdition($event);

        return Inertia::render('events/Show', [
            'event' => [
                'name' => $event->name,
                'slug' => $event->slug,
                'description' => $event->description,
                'cover_url' => $event->cover_path ? asset('storage/'.$event->cover_path) : null,
                'sport' => $event->sport->name,
                'organizer' => $event->organizer?->name,
            ],
            'edition' => $edition ? $this->editionPayload($edition) : null,
        ]);
    }

    public function preregister(Event $event): Response
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $edition = $this->currentEdition($event);

        return Inertia::render('events/Preregister', [
            'event' => [
                'name' => $event->name,
                'slug' => $event->slug,
            ],
            'edition' => $edition ? $this->editionPayload($edition) : null,
        ]);
    }

    private function currentEdition(Event $event): ?EventEdition
    {
        /** @var Collection<int, EventEdition> $editions */
        $editions = $event->editions()
            ->where('status', EditionStatus::Published)
            ->with(['races' => fn ($q) => $q->where('active', true)->orderBy('distance_value')])
            ->orderBy('event_date')
            ->get();

        return $editions->first(fn (EventEdition $edition) => ! $edition->event_date->isPast()) ?? $editions->last();
    }

    /**
     * @return array<string, mixed>
     */
    private function editionPayload(EventEdition $edition): array
    {
        return [
            'name' => $edition->name,
            'year' => $edition->year,
            'event_date' => $edition->event_date->toDateString(),
            'city' => $edition->city,
            'state' => $edition->state,
            'country' => $edition->country,
            'phase' => $edition->phase,
            'registration_open_at' => $edition->registration_open_at?->toDateString(),
            'registration_close_at' => $edition->registration_close_at?->toDateString(),
            'races' => $edition->races->map(fn ($race) => [
                'name' => $race->name,
                'distance_value' => $race->distance_value,
                'distance_unit' => $race->distance_unit,
                'start_time' => $race->start_time,
            ]),
        ];
    }
}
