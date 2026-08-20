<?php

namespace App\Services\Production;

use App\Enums\ProductionJobStatus;
use App\Exceptions\Production\InvalidProductionTransitionException;
use App\Models\ProductionJob;

/**
 * The one place that knows which ProductionJobStatus transitions are legal
 * — every Action in app/Actions/Production (used by both the web and
 * Device API controllers, see docs/adr/0003 §8) calls `assertAllowed()`
 * before mutating a job's status, instead of checking status by hand.
 *
 * Deliberately does not touch Plate — see PlateProductionCoordinator for
 * that sync, kept as a separate concern so this class stays a pure
 * job-workflow state machine.
 */
class ProductionJobStateMachine
{
    /**
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'queued' => ['assigned', 'cancelled'],
        'assigned' => ['preparing', 'queued', 'cancelled', 'failed'],
        'preparing' => ['engraving_front', 'queued', 'cancelled', 'failed'],
        'engraving_front' => ['awaiting_flip', 'failed'],
        'awaiting_flip' => ['engraving_back', 'failed'],
        'engraving_back' => ['verifying_qr', 'failed'],
        'verifying_qr' => ['ready', 'failed'],
        'ready' => ['delivered'],
        'delivered' => [],
        'failed' => [],
        'cancelled' => [],
    ];

    public function canTransition(ProductionJobStatus $from, ProductionJobStatus $to): bool
    {
        return in_array($to->value, self::TRANSITIONS[$from->value], true);
    }

    /**
     * @throws InvalidProductionTransitionException
     */
    public function assertAllowed(ProductionJob $job, ProductionJobStatus $to): void
    {
        if (! $this->canTransition($job->status, $to)) {
            throw new InvalidProductionTransitionException($job->status->value, $to->value);
        }
    }
}
