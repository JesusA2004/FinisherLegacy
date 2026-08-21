<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Athletes\ResolveAthleteIdentityConflict;
use App\Http\Controllers\Controller;
use App\Models\Athlete;
use App\Models\AthleteIdentityConflict;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Datos importados" vs. "posible atleta" — §23. Confidence is shown as a
 * band (Alta/Media), never a raw percentage presented as certainty (§85).
 */
class AthleteIdentityConflictController extends Controller
{
    public function index(): Response
    {
        $conflicts = AthleteIdentityConflict::query()
            ->where('status', 'pending')
            ->with(['candidateAthlete', 'eventParticipant.eventEdition.event'])
            ->orderBy('created_at')
            ->paginate(25);

        $conflicts->through(fn (AthleteIdentityConflict $conflict) => [
            'id' => $conflict->id,
            'source_type' => $conflict->source_type,
            'reason' => $conflict->reason,
            'confidence' => $conflict->confidence,
            'confidence_band' => $this->confidenceBand($conflict->confidence),
            'incoming' => $conflict->incoming_data,
            'event' => $conflict->eventParticipant?->eventEdition?->event?->name,
            'candidate' => $conflict->candidateAthlete ? [
                'id' => $conflict->candidateAthlete->id,
                'full_name' => $conflict->candidateAthlete->full_name,
                'email' => $conflict->candidateAthlete->email,
                'birth_date' => $conflict->candidateAthlete->birth_date?->toDateString(),
            ] : null,
            'candidates_count' => count($conflict->candidates ?? []),
            'created_at' => $conflict->created_at?->diffForHumans(),
        ]);

        return Inertia::render('admin/identity-conflicts/Index', ['conflicts' => $conflicts]);
    }

    public function resolve(Request $request, AthleteIdentityConflict $conflict, ResolveAthleteIdentityConflict $action): RedirectResponse
    {
        $data = $request->validate([
            'resolution' => ['required', Rule::in(['link_existing', 'create_new', 'ignore'])],
            'athlete_id' => ['nullable', 'integer', 'exists:athletes,id'],
        ]);

        $chosenAthlete = ! empty($data['athlete_id']) ? Athlete::find((int) $data['athlete_id']) : null;

        $action->handle($conflict, $data['resolution'], $request->user(), $chosenAthlete);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Conflicto resuelto.']);

        return back();
    }

    private function confidenceBand(?int $confidence): ?string
    {
        return match (true) {
            $confidence === null => null,
            $confidence >= 80 => 'Alta',
            $confidence >= 60 => 'Media',
            default => 'Baja',
        };
    }
}
