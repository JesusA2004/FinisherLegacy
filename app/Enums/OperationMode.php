<?php

namespace App\Enums;

enum OperationMode: string
{
    case Integrated = 'integrated';
    case Quick = 'quick';
    case Hybrid = 'hybrid';
}
