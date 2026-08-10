<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The Legacy Code exists but is blocked/cancelled/replaced — maps to 403
 * in both the web controller and the future API controller.
 */
class LegacyCodeUnavailableException extends RuntimeException {}
