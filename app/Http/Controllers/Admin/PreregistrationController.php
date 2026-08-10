<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventPreregistration;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PreregistrationController extends Controller
{
    public function index(Request $request): Response
    {
        $preregistrations = EventPreregistration::query()
            ->with(['eventEdition.event', 'eventRace'])
            ->when($request->string('q')->toString(), fn ($q, $search) => $q->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('bib_number', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $preregistrations->through(fn (EventPreregistration $preregistration) => [
            'id' => $preregistration->id,
            'name' => trim("{$preregistration->first_name} {$preregistration->last_name}"),
            'email' => $preregistration->email,
            'event' => $preregistration->eventEdition?->event?->name,
            'race' => $preregistration->eventRace?->name,
            'bib_number' => $preregistration->bib_number,
            'status' => $preregistration->status->value,
        ]);

        return Inertia::render('admin/preregistrations/Index', [
            'preregistrations' => $preregistrations,
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }
}
