<?php

namespace App\Http\Resources\Api\V1\EventOps;

use App\Models\EventParticipant;
use App\Models\Plate;
use App\Models\PlateTemplateVersion;
use App\Support\PlateEligibilityResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps the array App\Queries\Operations\GetParticipantOperationsDetail
 * returns — participant + result + splits + a minimal athlete summary +
 * plate + eligibility, no email/phone/birth_date (docs/api/v1.md §PII).
 * The athlete summary is deliberately thin: an operator needs to know
 * "this person has run before," not a CRM view of them (docs/adr/0004
 * §58-60 — the same restraint the admin Athlete screen already applies).
 */
class ParticipantDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{participant: EventParticipant, existingPlate: ?Plate, templateVersion: ?PlateTemplateVersion, eligibility: PlateEligibilityResult} $data */
        $data = $this->resource;
        $participant = $data['participant'];
        $athlete = $participant->athlete;

        return [
            'id' => $participant->id,
            'bib_number' => $participant->bib_number,
            'full_name' => $participant->full_name,
            'gender' => $participant->gender,
            'category' => $participant->category,
            'race' => $participant->eventRace?->name,
            'athlete' => $athlete === null ? null : [
                'id' => $athlete->id,
                'full_name' => $athlete->full_name,
                'previous_participations_count' => $athlete->eventParticipations()->count(),
            ],
            'result' => $participant->result === null ? null : [
                'official_time' => $participant->result->official_time,
                'pace' => $participant->result->pace,
                'status' => $participant->result->status->value,
                'overall_position' => $participant->result->overall_position,
                'category_position' => $participant->result->category_position,
                'manual_override' => $participant->result->manual_override_at !== null,
                'splits' => $participant->result->splits->map(fn ($split) => [
                    'label' => $split->label,
                    'sequence' => $split->sequence,
                    'elapsed_time' => $split->elapsed_time,
                    'segment_time' => $split->segment_time,
                ]),
            ],
            'template_name' => $data['templateVersion'] === null
                ? null
                : "{$data['templateVersion']->plateTemplate->name} — V{$data['templateVersion']->version}",
            'plate' => $data['existingPlate'] === null ? null : new PlateResource($data['existingPlate']),
            'eligibility' => [
                'eligible' => $data['eligibility']->eligible,
                'reasons' => $data['eligibility']->reasons,
            ],
        ];
    }
}
