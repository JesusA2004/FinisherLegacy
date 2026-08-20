<?php

namespace App\Enums;

/**
 * A pairing request's lifecycle: Pending (desktop requested, waiting for a
 * Super Admin) -> Approved (linked to a ProductionDevice, token not yet
 * delivered) -> Completed (token delivered exactly once, code now inert).
 * Expired is terminal, set lazily when a Pending/Approved request is seen
 * past `expires_at` rather than by a sweeping job.
 */
enum DevicePairingStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Completed = 'completed';
    case Expired = 'expired';
}
