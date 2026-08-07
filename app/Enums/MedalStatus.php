<?php

namespace App\Enums;

enum MedalStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
