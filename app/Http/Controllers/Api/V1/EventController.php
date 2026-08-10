<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventStatus;
use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventEditionCardResource;
use App\Models\Event;
use App\Services\EventCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly EventCatalogService $events) {}

    public function index(Request $request): JsonResponse
    {
        $editions = $this->events->publishedEditions([
            'q' => $request->query('q'),
            'sport' => $request->query('sport'),
            'status' => $request->query('status'),
        ]);

        return EventEditionCardResource::collection($editions)->response();
    }

    public function show(Event $event): JsonResponse
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $event->load(['organizer', 'sport']);
        $edition = $this->events->currentEdition($event);

        return $this->respond([
            'name' => $event->name,
            'slug' => $event->slug,
            'description' => $event->description,
            'cover_url' => $event->cover_path ? asset('storage/'.$event->cover_path) : null,
            'sport' => $event->sport->name,
            'organizer' => $event->organizer?->name,
            'edition' => $edition ? [
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
            ] : null,
        ]);
    }
}
