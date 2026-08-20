<?php

namespace App\Exceptions\Integrations;

use RuntimeException;

/**
 * A result arrived before its participant (docs/adr/0005 §77) — a
 * row-level, retryable condition. SyncExternalResults catches this per-row
 * and records ExternalSyncError code=PARTICIPANT_NOT_FOUND instead of
 * losing the result or failing the whole sync; the next sync retries it.
 */
class ParticipantNotFoundException extends RuntimeException {}
