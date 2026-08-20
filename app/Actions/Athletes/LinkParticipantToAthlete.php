<?php

namespace App\Actions\Athletes;

use App\Models\Athlete;
use App\Models\EventParticipant;

class LinkParticipantToAthlete
{
    public function handle(EventParticipant $participant, Athlete $athlete): EventParticipant
    {
        $participant->update(['athlete_id' => $athlete->id]);

        return $participant->fresh();
    }
}
