<?php

namespace App\Enums;

enum StaffRole: string
{
    case EventManager = 'event_manager';
    case EventOperator = 'event_operator';
    case ProductionOperator = 'production_operator';
}
