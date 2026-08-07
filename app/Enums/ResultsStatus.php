<?php

namespace App\Enums;

enum ResultsStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Official = 'official';
    case Revised = 'revised';
}
