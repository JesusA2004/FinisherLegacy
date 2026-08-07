<?php

namespace App\Enums;

enum ImportType: string
{
    case Participants = 'participants';
    case Results = 'results';
}
