<?php

use App\Enums\ProductionJobStatus;
use App\Models\EventEdition;
use App\Models\ProductionJob;
use App\Queries\Production\GetProductionMetrics;

test('returns null when nothing has been delivered yet', function () {
    $edition = EventEdition::factory()->create();
    ProductionJob::factory()->create(['event_edition_id' => $edition->id, 'status' => ProductionJobStatus::Queued]);

    expect(app(GetProductionMetrics::class)->handle($edition))->toBeNull();
});

test('averages real timestamp gaps from delivered jobs only', function () {
    $edition = EventEdition::factory()->create();
    $queuedAt = now()->subMinutes(10);

    ProductionJob::factory()->create([
        'event_edition_id' => $edition->id,
        'status' => ProductionJobStatus::Delivered,
        'queued_at' => $queuedAt,
        'delivered_at' => $queuedAt->copy()->addMinutes(2),
    ]);
    // A queued job (never delivered) must never pollute the average.
    ProductionJob::factory()->create(['event_edition_id' => $edition->id, 'status' => ProductionJobStatus::Queued]);

    $metrics = app(GetProductionMetrics::class)->handle($edition);

    expect($metrics)->not->toBeNull()
        ->and($metrics['sample_size'])->toBe(1)
        ->and($metrics['total_duration'])->toBe(120);
});
