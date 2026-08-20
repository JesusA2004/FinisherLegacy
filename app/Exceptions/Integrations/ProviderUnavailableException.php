<?php

namespace App\Exceptions\Integrations;

use RuntimeException;

/**
 * Unlike a row-level error (App\Models\ExternalSyncError), this one fails
 * the whole sync run — auth failure, provider down, timeout exhausted
 * (docs/adr/0005 §37).
 */
class ProviderUnavailableException extends RuntimeException {}
