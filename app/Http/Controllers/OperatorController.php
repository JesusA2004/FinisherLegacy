<?php

namespace App\Http\Controllers;

use App\Enums\EditionStatus;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\Plate;
use App\Models\PlateTemplate;
use App\Services\PlateGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OperatorController extends Controller
{
    public function __construct(private readonly PlateGenerationService $plates) {}

    public function index(Request $request): Response
    {
        $activeEdition = $this->activeEdition($request);

        return Inertia::render('operator/Index', [
            'editions' => EventEdition::query()
                ->whereIn('status', [EditionStatus::Published, EditionStatus::InProgress])
                ->with('event')
                ->orderByDesc('event_date')
                ->limit(50)
                ->get()
                ->map(fn (EventEdition $edition) => [
                    'id' => $edition->id,
                    'name' => $edition->event->name.' — '.$edition->name,
                ]),
            'activeEdition' => $activeEdition ? [
                'id' => $activeEdition->id,
                'name' => $activeEdition->event->name.' — '.$activeEdition->name,
            ] : null,
        ]);
    }

    public function selectEvent(Request $request): RedirectResponse
    {
        $request->validate(['event_edition_id' => ['required', 'integer', 'exists:event_editions,id']]);

        $request->session()->put('operator_event_edition_id', $request->integer('event_edition_id'));

        return back();
    }

    public function search(Request $request): JsonResponse
    {
        $editionId = $request->session()->get('operator_event_edition_id');
        abort_unless($editionId, 422);

        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json(['data' => []]);
        }

        $participants = EventParticipant::query()
            ->where('event_edition_id', $editionId)
            ->where(function ($q) use ($query) {
                $q->where('bib_number', $query)
                    ->orWhere('full_name', 'like', "%{$query}%")
                    ->orWhere('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->with(['eventRace', 'result'])
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $participants->map(fn (EventParticipant $participant) => [
                'id' => $participant->id,
                'bib_number' => $participant->bib_number,
                'full_name' => $participant->full_name,
                'race' => $participant->eventRace?->name,
                'has_result' => $participant->result !== null,
                'has_plate' => Plate::where('event_participant_id', $participant->id)->exists(),
            ]),
        ]);
    }

    public function showParticipant(EventParticipant $eventParticipant): Response
    {
        $eventParticipant->load(['eventRace', 'eventEdition.event', 'result', 'user']);
        $existingPlate = Plate::where('event_participant_id', $eventParticipant->id)->first();

        return Inertia::render('operator/Participant', [
            'participant' => [
                'id' => $eventParticipant->id,
                'bib_number' => $eventParticipant->bib_number,
                'full_name' => $eventParticipant->full_name,
                'race' => $eventParticipant->eventRace?->name,
                'official_time' => $eventParticipant->result?->official_time,
                'pace' => $eventParticipant->result?->pace,
                'result_status' => $eventParticipant->result?->status->value,
            ],
            'existingPlate' => $existingPlate ? $this->plates->previewPayload($existingPlate) : null,
        ]);
    }

    public function generateIntegratedPlate(EventParticipant $eventParticipant): RedirectResponse
    {
        abort_if(Plate::where('event_participant_id', $eventParticipant->id)->exists(), 409, 'Esta persona ya tiene una placa generada.');

        $template = PlateTemplate::where('active', true)->first();
        $plate = $this->plates->generateIntegrated($eventParticipant, $template);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Placa generada y enviada a producción.']);

        return redirect()->route('operator.participants.show', $eventParticipant)->with('plateId', $plate->id);
    }

    public function generateQuickPlate(Request $request): RedirectResponse
    {
        $edition = $this->activeEdition($request);
        abort_unless($edition !== null, 422);

        $data = $request->validate([
            'athlete_name' => ['required', 'string', 'max:150'],
            'bib_number' => ['nullable', 'string', 'max:20'],
            'race_name' => ['nullable', 'string', 'max:50'],
            'official_time' => ['nullable', 'string', 'max:20'],
            'pace' => ['nullable', 'string', 'max:20'],
        ]);

        $template = PlateTemplate::where('active', true)->first();

        $plate = $this->plates->generateQuick($edition, $data, $template);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Placa rápida generada y enviada a producción.']);

        return to_route('operator.index')->with('plateId', $plate->id);
    }

    private function activeEdition(Request $request): ?EventEdition
    {
        $editionId = $request->session()->get('operator_event_edition_id');

        if (! is_numeric($editionId)) {
            return null;
        }

        return EventEdition::with('event')->find((int) $editionId);
    }
}
