<?php

use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\EventRace;
use App\Queries\Operations\SearchEventParticipants;
use Illuminate\Support\Facades\DB;

/**
 * Not a benchmark (§106 del prompt: "no benchmark absurdo") — just proof
 * that App\Queries\Operations\SearchEventParticipants issues a fixed,
 * small number of queries no matter how many rows match, i.e. no N+1
 * hiding in the eager-loaded relations/`withExists()`. Measured directly
 * against the Query class, not over HTTP, so auth/permission-check
 * overhead (which Spatie caches differently across requests) doesn't
 * pollute the count.
 */
test('participant search issues a fixed, small number of queries regardless of result count', function () {
    $edition = EventEdition::factory()->create();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $search = app(SearchEventParticipants::class);

    EventParticipant::factory()->count(3)->create([
        'event_edition_id' => $edition->id, 'event_race_id' => $race->id, 'last_name' => 'Torres',
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $few = $search->handle($edition, 'Torres');
    $queriesForFew = count(DB::getQueryLog());

    EventParticipant::factory()->count(27)->create([
        'event_edition_id' => $edition->id, 'event_race_id' => $race->id, 'last_name' => 'Torres',
    ]);

    DB::flushQueryLog();
    $many = $search->handle($edition, 'Torres');
    $queriesForMany = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($few)->toHaveCount(3)
        ->and($many)->toHaveCount(20)
        ->and($queriesForMany)->toBe($queriesForFew)
        ->and($queriesForFew)->toBeLessThanOrEqual(3);
});
