<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Registered = 'registered';
    case Cancelled = 'cancelled';
    case Transferred = 'transferred';
    case Disqualified = 'disqualified';
}
