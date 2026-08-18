<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by PlateGenerationService before any row is written whenever the
 * event edition has no default plate template version resolved — a Plate
 * (and its Legacy Code / ProductionJob) must never be created without one,
 * or export/production tooling breaks downstream on a NULL template.
 */
class PlateTemplateMissingException extends RuntimeException {}
