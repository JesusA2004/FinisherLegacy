<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The Legacy Code is already claimed by a different user — maps to 409 in
 * both the web controller and the future API controller. Never expose who
 * the current owner is.
 */
class LegacyCodeClaimConflictException extends RuntimeException {}
