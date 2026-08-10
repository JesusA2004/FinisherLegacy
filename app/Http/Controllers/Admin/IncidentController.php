<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Models\EventIncident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    public function index(Request $request): Response
    {
        $incidents = EventIncident::query()
            ->with(['eventEdition.event', 'reportedBy'])
            ->when($request->string('q')->toString(), fn ($q, $search) => $q->where('description', 'like', "%{$search}%"))
            ->orderByRaw("status = 'open' desc")
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $incidents->through(fn (EventIncident $incident) => [
            'id' => $incident->id,
            'event' => $incident->eventEdition?->event?->name,
            'type' => $incident->type->value,
            'description' => str($incident->description)->limit(80)->toString(),
            'status' => $incident->status->value,
            'reported_by' => $incident->reportedBy->name ?? '—',
        ]);

        return Inertia::render('admin/incidents/Index', [
            'incidents' => $incidents,
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }

    public function resolve(Request $request, EventIncident $incident): RedirectResponse
    {
        abort_if($incident->status === IncidentStatus::Resolved, 409, 'Esta incidencia ya está resuelta.');

        $incident->update([
            'status' => IncidentStatus::Resolved,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Incidencia marcada como resuelta.']);

        return back();
    }
}
