<?php

namespace App\Enums;

enum PlateGenerationMode: string
{
    case Integrated = 'integrated';
    case Quick = 'quick';
    case Manual = 'manual';
}
