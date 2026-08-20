<?php

namespace App\Enums;

/**
 * The physical engraving workflow — see docs/adr/0003-production-state-machine.md.
 * This is `ProductionJob`'s state machine, not `Plate`'s: a Plate represents
 * the *product* (queued/processing/ready/delivered/reprint/cancelled), a
 * ProductionJob represents one physical attempt at engraving it. A Plate
 * can have several jobs over its life (reprints) — never mix the two.
 *
 * `Processing`/`Completed` (the Slice 1 coarse states) no longer exist as
 * cases — see the `2026_08_21_100000_migrate_production_job_status_values`
 * migration for how existing rows are rewritten instead of left as invalid
 * strings.
 */
enum ProductionJobStatus: string
{
    case Queued = 'queued';
    case Assigned = 'assigned';
    case Preparing = 'preparing';
    case EngravingFront = 'engraving_front';
    case AwaitingFlip = 'awaiting_flip';
    case EngravingBack = 'engraving_back';
    case VerifyingQr = 'verifying_qr';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Failed, self::Cancelled], true);
    }
}
