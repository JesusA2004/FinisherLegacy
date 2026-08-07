<?php

namespace App\Enums;

enum LegacyCodeStatus: string
{
    case Generated = 'generated';
    case Available = 'available';
    case Assigned = 'assigned';
    case Claimed = 'claimed';
    case Blocked = 'blocked';
    case Replaced = 'replaced';
    case Cancelled = 'cancelled';
}
