<?php

namespace App\Exceptions;

use App\Enums\ApiErrorCode;
use App\Exceptions\Api\ApiException;

/**
 * Thrown by PlateGenerationService::generateIntegrated() when the
 * participant already has a Plate — the single place this idempotency
 * guard lives (docs/adr/0006-event-operations.md §5), reused by both the
 * Web operator console and the API. See App\Exceptions\PlateTemplateMissingException
 * for why this extends App\Exceptions\Api\ApiException.
 */
class PlateAlreadyExistsException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            'Esta persona ya tiene una placa generada.',
            ApiErrorCode::PlateAlreadyExists,
            409,
        );
    }
}
