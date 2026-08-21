<?php

namespace App\Enums;

/**
 * Machine-readable codes for the general `/api/v1/*` surface's
 * `{"error": {"code", ...}}` contract (docs/api/v1.md §Errores) — mirrors
 * App\Enums\DeviceErrorCode's shape for the Device API, kept as a
 * separate enum because the Device API's contract is already documented/
 * shipped and must not shift. A client branches on `code`, never on the
 * human `message` text, which is allowed to change.
 */
enum ApiErrorCode: string
{
    case ValidationFailed = 'VALIDATION_FAILED';
    case Unauthenticated = 'UNAUTHENTICATED';
    case Forbidden = 'FORBIDDEN';
    case NotFound = 'NOT_FOUND';
    case Conflict = 'CONFLICT';
    case TooManyRequests = 'TOO_MANY_REQUESTS';
    case HttpError = 'HTTP_ERROR';
    case InternalError = 'INTERNAL_ERROR';

    case PlateAlreadyExists = 'PLATE_ALREADY_EXISTS';
    case PlateTemplateMissing = 'PLATE_TEMPLATE_MISSING';
    case ParticipantNotEligible = 'PARTICIPANT_NOT_ELIGIBLE';
}
