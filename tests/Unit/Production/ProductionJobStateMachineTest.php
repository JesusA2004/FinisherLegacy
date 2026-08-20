<?php

use App\Enums\ProductionJobStatus;
use App\Exceptions\Production\InvalidProductionTransitionException;
use App\Models\ProductionJob;
use App\Services\Production\ProductionJobStateMachine;

beforeEach(function () {
    $this->machine = new ProductionJobStateMachine;
});

$validTransitions = [
    ['queued', 'assigned'],
    ['queued', 'cancelled'],
    ['assigned', 'preparing'],
    ['assigned', 'queued'],
    ['assigned', 'cancelled'],
    ['assigned', 'failed'],
    ['preparing', 'engraving_front'],
    ['preparing', 'queued'],
    ['preparing', 'cancelled'],
    ['preparing', 'failed'],
    ['engraving_front', 'awaiting_flip'],
    ['engraving_front', 'failed'],
    ['awaiting_flip', 'engraving_back'],
    ['awaiting_flip', 'failed'],
    ['engraving_back', 'verifying_qr'],
    ['engraving_back', 'failed'],
    ['verifying_qr', 'ready'],
    ['verifying_qr', 'failed'],
    ['ready', 'delivered'],
];

test('every documented transition is allowed', function (string $from, string $to) {
    expect($this->machine->canTransition(ProductionJobStatus::from($from), ProductionJobStatus::from($to)))->toBeTrue();
})->with($validTransitions);

$invalidTransitions = [
    ['queued', 'ready'],
    ['queued', 'engraving_front'],
    ['queued', 'delivered'],
    ['assigned', 'delivered'],
    ['assigned', 'engraving_front'],
    ['preparing', 'awaiting_flip'],
    ['engraving_front', 'engraving_back'],
    ['engraving_front', 'ready'],
    ['engraving_front', 'cancelled'],
    ['awaiting_flip', 'verifying_qr'],
    ['awaiting_flip', 'cancelled'],
    ['engraving_back', 'ready'],
    ['engraving_back', 'cancelled'],
    ['verifying_qr', 'delivered'],
    ['verifying_qr', 'cancelled'],
    ['ready', 'engraving_back'],
    ['ready', 'failed'],
    ['ready', 'cancelled'],
    ['delivered', 'ready'],
    ['failed', 'queued'],
    ['cancelled', 'queued'],
];

test('the listed invalid transitions are rejected', function (string $from, string $to) {
    expect($this->machine->canTransition(ProductionJobStatus::from($from), ProductionJobStatus::from($to)))->toBeFalse();
})->with($invalidTransitions);

test('assertAllowed throws a domain exception with the from/to in its details', function () {
    $job = new ProductionJob(['status' => ProductionJobStatus::Queued]);

    try {
        $this->machine->assertAllowed($job, ProductionJobStatus::Ready);
        $this->fail('Expected InvalidProductionTransitionException');
    } catch (InvalidProductionTransitionException $e) {
        expect($e->details())->toBe(['from' => 'queued', 'to' => 'ready'])
            ->and($e->status())->toBe(409);
    }
});
