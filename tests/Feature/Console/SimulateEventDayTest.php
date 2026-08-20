<?php

use App\Enums\ProductionJobStatus;
use App\Models\Plate;
use App\Models\ProductionJob;
use Illuminate\Support\Facades\Artisan;

test('finisher:simulate-event-day runs the full criterion end to end', function () {
    $exitCode = Artisan::call('finisher:simulate-event-day', ['--bib' => '1010', '--finishers' => 15]);

    expect($exitCode)->toBe(0);

    $plate = Plate::where('bib_number', '1010')->first();
    expect($plate)->not->toBeNull();

    $job = ProductionJob::where('plate_id', $plate->id)->firstOrFail();
    expect($job->status)->toBe(ProductionJobStatus::Delivered)
        ->and($job->front_engraved_at)->not->toBeNull()
        ->and($job->back_engraved_at)->not->toBeNull()
        ->and($job->qr_verified_at)->not->toBeNull()
        ->and($job->delivered_at)->not->toBeNull();
});
