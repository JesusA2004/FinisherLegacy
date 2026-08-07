<?php

namespace App\Enums;

enum ParticipantSource: string
{
    case Manual = 'manual';
    case Csv = 'csv';
    case Xlsx = 'xlsx';
    case Preregistration = 'preregistration';
    case Api = 'api';
}
