<?php

namespace App\Queries\Operations;

use App\Models\EventParticipant;
use App\Models\Plate;
use App\Models\PlateTemplateVersion;
use App\Services\PlateEligibilityService;
use App\Support\PlateEligibilityResult;

/**
 * Everything the "participant detail" screen needs — Web
 * (App\Http\Controllers\OperatorController::showParticipant()) and the API
 * (App\Http\Controllers\Api\V1\EventOpsController::participant()) both call
 * this instead of each re-assembling the same joins (docs/api/use-case-matrix.md).
 * Returns raw models/value objects, not a transport-specific shape — Web
 * builds Inertia props, the API wraps this in a Resource; neither
 * duplicates the query/eligibility logic itself.
 */
class GetParticipantOperationsDetail
{
    public function __construct(private readonly PlateEligibilityService $eligibility) {}

    /**
     * @return array{participant: EventParticipant, existingPlate: ?Plate, templateVersion: ?PlateTemplateVersion, eligibility: PlateEligibilityResult}
     */
    public function handle(EventParticipant $participant): array
    {
        $participant->loadMissing(['eventRace', 'eventEdition.event', 'result.splits', 'user', 'athlete']);

        return [
            'participant' => $participant,
            'existingPlate' => Plate::where('event_participant_id', $participant->id)->first(),
            'templateVersion' => $participant->eventEdition->defaultPlateTemplateVersion($participant->event_race_id),
            'eligibility' => $this->eligibility->check($participant),
        ];
    }
}
