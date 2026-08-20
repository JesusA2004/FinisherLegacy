<?php

namespace App\Enums;

enum AthleteIdentityConflictStatus: string
{
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Ignored = 'ignored';
}
