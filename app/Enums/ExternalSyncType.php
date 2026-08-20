<?php

namespace App\Enums;

enum ExternalSyncType: string
{
    case Roster = 'roster';
    case Results = 'results';
    case Full = 'full';
}
