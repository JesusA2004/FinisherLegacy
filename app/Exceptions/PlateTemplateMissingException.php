<?php

namespace App\Exceptions;

use App\Enums\ApiErrorCode;
use App\Exceptions\Api\ApiException;

/**
 * Thrown by PlateGenerationService before any row is written whenever the
 * event edition has no default plate template version resolved — a Plate
 * (and its Legacy Code / ProductionJob) must never be created without one,
 * or export/production tooling breaks downstream on a NULL template.
 *
 * Extends App\Exceptions\Api\ApiException so an API caller gets a proper
 * `{"error": {"code": "PLATE_TEMPLATE_MISSING", ...}}` automatically
 * (App\Support\Api\ApiExceptionRenderer) — App\Http\Controllers\OperatorController
 * (Web) still catches this explicitly for its redirect+toast UX, unchanged.
 */
class PlateTemplateMissingException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            'Este evento no tiene un molde de producción asignado.',
            ApiErrorCode::PlateTemplateMissing,
            422,
        );
    }
}
