<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Http\Requests\StorePreregistrationRequest;
use App\Http\Resources\EventEditionCardResource;
use App\Models\Event;
use App\Models\EventEdition;
use App\Models\EventRace;
use App\Models\Sport;
use App\Services\EventCatalogService;
use App\Services\PreregistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct(
        private readonly EventCatalogService $events,
        private readonly PreregistrationService $preregistrations,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'sport' => $request->query('sport'),
            'status' => $request->query('status'),
        ];

        $editions = $this->events->publishedEditions($filters);
        $editions->through(fn ($edition) => (new EventEditionCardResource($edition))->resolve());

        return Inertia::render('events/Index', [
            'editions' => $editions,
            'sports' => Sport::query()->where('active', true)->orderBy('sort_order')->get(['id', 'name', 'slug']),
            'filters' => [
                'q' => $filters['q'],
                'sport' => $filters['sport'],
                'status' => $filters['status'],
            ],
        ]);
    }

    public function show(Event $event): Response
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $event->load(['organizer', 'sport']);
        $edition = $this->events->currentEdition($event);

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

    public function preregister(Request $request, Event $event): Response
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $edition = $this->events->currentEdition($event);
        $user = $request->user();

        return Inertia::render('events/Preregister', [
            'event' => [
                'name' => $event->name,
                'slug' => $event->slug,
            ],
            'edition' => $edition ? $this->editionPayload($edition) : null,
            'isOpen' => $edition ? $this->preregistrations->isOpen($edition) : false,
            'prefill' => $user ? [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
            ] : null,
        ]);
    }

    public function storePreregistration(StorePreregistrationRequest $request, Event $event): RedirectResponse
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $edition = $this->events->currentEdition($event);
        abort_unless($edition && $this->preregistrations->isOpen($edition), 403);

        $race = EventRace::query()
            ->where('event_edition_id', $edition->id)
            ->findOrFail($request->integer('event_race_id'));

        $preregistration = $this->preregistrations->create(
            $edition,
            $race,
            $request->safe()->except('event_race_id'),
            $request->user(),
        );

        return redirect()->route('preregistrations.show', $preregistration->token);
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
                'id' => $race->id,
                'name' => $race->name,
                'distance_value' => $race->distance_value,
                'distance_unit' => $race->distance_unit,
                'start_time' => $race->start_time,
            ]),
        ];
    }
}
