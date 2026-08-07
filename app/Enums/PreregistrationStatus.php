<?php

namespace App\Enums;

enum PreregistrationStatus: string
{
    case Pending = 'pending';
    case Matched = 'matched';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
