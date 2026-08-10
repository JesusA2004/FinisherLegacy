<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlateController extends Controller
{
    public function index(Request $request): Response
    {
        $plates = Plate::query()
            ->with(['legacyCode', 'eventEdition.event'])
            ->when($request->string('q')->toString(), fn ($q, $search) => $q->where(function ($query) use ($search) {
                $query->where('athlete_name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('bib_number', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $plates->through(fn (Plate $plate) => [
            'id' => $plate->id,
            'serial_number' => $plate->serial_number,
            'athlete_name' => $plate->athlete_name,
            'event' => $plate->eventEdition?->event->name ?? $plate->event_name,
            'status' => $plate->status->value,
            'generation_mode' => $plate->generation_mode->value,
            'legacy_code' => $plate->legacyCode?->code,
            'owner' => $plate->user_id ? 'Vinculada' : 'Sin reclamar',
        ]);

        return Inertia::render('admin/plates/Index', [
            'plates' => $plates,
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }
}
