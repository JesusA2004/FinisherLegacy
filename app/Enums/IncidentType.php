<?php

namespace App\Enums;

enum IncidentType: string
{
    case IncorrectName = 'incorrect_name';
    case IncorrectResult = 'incorrect_result';
    case ResultNotFound = 'result_not_found';
    case DuplicateBib = 'duplicate_bib';
    case QrProblem = 'qr_problem';
    case PrintFailure = 'print_failure';
    case DamagedPlate = 'damaged_plate';
    case ReprintRequired = 'reprint_required';
    case Other = 'other';
}
