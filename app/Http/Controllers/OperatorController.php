<?php

namespace App\Http\Controllers;

use App\Enums\EditionStatus;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\Plate;
use App\Services\PlateGenerationService;
use App\Services\PlateTemplateRenderService;
use App\Support\PlateRenderData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OperatorController extends Controller
{
    public function __construct(
        private readonly PlateGenerationService $plates,
        private readonly PlateTemplateRenderService $renderer,
    ) {}

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
                'hasTemplate' => $activeEdition->defaultPlateTemplateVersion() !== null,
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
        $version = $eventParticipant->eventEdition->defaultPlateTemplateVersion($eventParticipant->event_race_id);

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
            'templateName' => $version ? "{$version->plateTemplate->name} — V{$version->version}" : null,
            'existingPlate' => $existingPlate ? $this->plates->previewPayload($existingPlate) : null,
        ]);
    }

    public function generateIntegratedPlate(EventParticipant $eventParticipant): RedirectResponse
    {
        abort_if(Plate::where('event_participant_id', $eventParticipant->id)->exists(), 409, 'Esta persona ya tiene una placa generada.');

        $plate = $this->plates->generateIntegrated($eventParticipant);

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
            'swim_time' => ['nullable', 'string', 'max:20'],
            'bike_time' => ['nullable', 'string', 'max:20'],
            'run_time' => ['nullable', 'string', 'max:20'],
            'personal_phrase' => ['nullable', 'string', 'max:150'],
        ]);

        $plate = $this->plates->generateQuick($edition, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Placa rápida generada y enviada a producción.']);

        return to_route('operator.quick-plate.show', $plate);
    }

    public function showQuickPlate(Plate $plate): Response
    {
        abort_unless($plate->generation_mode->value === 'quick', 404);

        return Inertia::render('operator/QuickPlateResult', [
            'plate' => $this->plates->previewPayload($plate),
        ]);
    }

    /**
     * Pre-generation preview: renders the edition's assigned template with draft
     * data (participant or quick-plate form) that has no Legacy Code yet — a plate
     * is never actually created here, this is purely a look-before-you-confirm step.
     */
    public function previewPlate(Request $request): JsonResponse
    {
        $edition = $this->activeEdition($request);
        abort_unless($edition !== null, 422);

        $data = $request->validate([
            'event_participant_id' => ['nullable', 'integer', 'exists:event_participants,id'],
            'quick' => ['nullable', 'array'],
            'face' => ['required', Rule::in(['front', 'back'])],
            'mode' => ['required', Rule::in(['product', 'production'])],
        ]);

        if (! empty($data['event_participant_id'])) {
            $participant = EventParticipant::with(['eventEdition.event', 'eventRace', 'result'])
                ->where('id', $data['event_participant_id'])
                ->firstOrFail();
            $version = $edition->defaultPlateTemplateVersion($participant->event_race_id);
            $renderData = PlateRenderData::fromArray([
                'athlete_name' => $participant->full_name,
                'bib_number' => $participant->bib_number,
                'event_name' => $participant->eventEdition->event->name,
                'event_date' => $participant->eventEdition->event_date->translatedFormat('d \d\e F \d\e Y'),
                'race_name' => $participant->eventRace?->name,
                'official_time' => $participant->result?->official_time,
                'pace' => $participant->result?->pace,
                'category' => $participant->category,
                'overall_position' => $participant->result?->overall_position !== null ? (string) $participant->result->overall_position : null,
                'category_position' => $participant->result?->category_position !== null ? (string) $participant->result->category_position : null,
                'legacy_code' => 'FL-PREVIEW',
                'plate_serial' => 'FL-PREVIEW',
            ], true);
        } else {
            $quick = $data['quick'] ?? [];
            $version = $edition->defaultPlateTemplateVersion();
            $renderData = PlateRenderData::fromArray([
                'athlete_name' => $quick['athlete_name'] ?? '',
                'bib_number' => $quick['bib_number'] ?? null,
                'event_name' => $quick['event_name'] ?? $edition->event->name,
                'event_date' => $edition->event_date->translatedFormat('d \d\e F \d\e Y'),
                'race_name' => $quick['race_name'] ?? null,
                'official_time' => $quick['official_time'] ?? null,
                'pace' => $quick['pace'] ?? null,
                'swim_time' => $quick['swim_time'] ?? null,
                'bike_time' => $quick['bike_time'] ?? null,
                'run_time' => $quick['run_time'] ?? null,
                'personal_phrase' => $quick['personal_phrase'] ?? null,
                'legacy_code' => 'FL-PREVIEW',
                'plate_serial' => 'FL-PREVIEW',
            ], true);
        }

        if (! $version) {
            return response()->json([
                'svg' => null,
                'warnings' => [],
                'error' => 'Este evento no tiene un molde de placa asignado. Configúralo en "Preparar evento para producción".',
            ]);
        }

        return response()->json([
            'svg' => $this->renderer->renderSvg($version, $data['face'], $renderData, $data['mode']),
            'warnings' => $this->renderer->warnings($version, $data['face'], $renderData)['warnings'],
            'error' => null,
        ]);
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
