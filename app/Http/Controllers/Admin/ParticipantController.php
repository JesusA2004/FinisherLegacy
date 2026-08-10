<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventParticipant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantController extends Controller
{
    public function index(Request $request): Response
    {
        $participants = EventParticipant::query()
            ->with(['eventEdition.event', 'eventRace', 'result'])
            ->when($request->string('q')->toString(), fn ($q, $search) => $q->where(function ($query) use ($search) {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('bib_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $participants->through(fn (EventParticipant $participant) => [
            'id' => $participant->id,
            'name' => $participant->full_name,
            'bib_number' => $participant->bib_number,
            'event' => $participant->eventEdition?->event?->name,
            'race' => $participant->eventRace?->name,
            'has_result' => $participant->result !== null ? 'Sí' : 'No',
            'source' => $participant->source->value,
        ]);

        return Inertia::render('admin/participants/Index', [
            'participants' => $participants,
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }
}
