<?php

namespace App\Services;

use App\Models\EventParticipant;
use App\Support\PlateEligibilityResult;

/**
 * The one place that decides "can this participant get an integrated
 * Plate right now" — Event Ops (Slice 5) and any future surface ask this
 * instead of re-deriving the rule (docs/adr/0005-unified-event-ingestion.md
 * §Plate eligibility). Deliberately never produces a Plate itself and
 * never runs automatically after a sync (§121-123) — an operator always
 * decides when to press "producir".
 */
class PlateEligibilityService
{
    public function check(EventParticipant $participant): PlateEligibilityResult
    {
        $participant->loadMissing(['result', 'eventEdition', 'identityConflicts', 'plates']);

        $reasons = [];

        if ($participant->identityConflicts->contains(fn ($c) => $c->status->value === 'pending')) {
            $reasons[] = 'IDENTITY_CONFLICT';
        }

        if ($participant->result === null || $participant->result->official_time === null) {
            $reasons[] = 'NO_RESULT';
        }

        if ($participant->eventEdition?->defaultPlateTemplateVersion($participant->event_race_id) === null) {
            $reasons[] = 'NO_TEMPLATE';
        }

        if ($participant->plates->isNotEmpty()) {
            $reasons[] = 'PLATE_ALREADY_EXISTS';
        }

        return new PlateEligibilityResult(eligible: $reasons === [], reasons: $reasons);
    }
}
