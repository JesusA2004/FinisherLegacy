<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventEdition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EditionController extends Controller
{
    public function index(Request $request): Response
    {
        $editions = EventEdition::query()
            ->with('event')
            ->when($request->string('q')->toString(), fn ($q, $search) => $q->whereHas(
                'event',
                fn ($eq) => $eq->where('name', 'like', "%{$search}%"),
            ))
            ->orderByDesc('event_date')
            ->paginate(25)
            ->withQueryString();

        $editions->through(fn (EventEdition $edition) => [
            'id' => $edition->id,
            'event' => $edition->event->name,
            'edition' => $edition->name,
            'event_date' => $edition->event_date->toDateString(),
            'status' => $edition->status->value,
            'phase' => $edition->phase,
        ]);

        return Inertia::render('admin/editions/Index', [
            'editions' => $editions,
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }
}
