<?php

namespace App\Http\Controllers;

use App\Enums\PlateStatus;
use App\Models\Plate;
use App\Services\ProductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductionController extends Controller
{
    public function __construct(private readonly ProductionService $production) {}

    public function index(): Response
    {
        $plates = Plate::query()
            ->whereNotNull('legacy_code_id')
            ->with(['legacyCode', 'eventEdition.event'])
            ->orderByDesc('updated_at')
            ->limit(300)
            ->get();

        $columns = [];

        foreach (ProductionService::COLUMNS as $key => $statuses) {
            $columns[$key] = $plates
                ->whereIn('status', array_map(fn (string $s) => PlateStatus::from($s), $statuses))
                ->values()
                ->map(fn (Plate $plate) => $this->cardPayload($plate));
        }

        return Inertia::render('production/Index', ['columns' => $columns]);
    }

    public function updateStatus(Request $request, Plate $plate): RedirectResponse
    {
        $request->validate([
            'status' => ['required', Rule::enum(PlateStatus::class)],
        ]);

        $this->production->transition(
            $plate,
            PlateStatus::from($request->string('status')->toString()),
            $request->user(),
        );

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function cardPayload(Plate $plate): array
    {
        return [
            'id' => $plate->id,
            'serial_number' => $plate->serial_number,
            'athlete_name' => $plate->athlete_name,
            'bib_number' => $plate->bib_number,
            'event_name' => $plate->eventEdition?->event->name ?? $plate->event_name,
            'status' => $plate->status->value,
            'legacy_code' => $plate->legacyCode?->code,
            'generation_mode' => $plate->generation_mode->value,
            'updated_at' => $plate->updated_at?->diffForHumans(),
        ];
    }
}
