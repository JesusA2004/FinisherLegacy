<?php

use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Models\EventEdition;
use App\Models\EventPreregistration;
use App\Models\EventRace;

test('a preregistration can be created via the api', function () {
    $edition = EventEdition::factory()->create([
        'status' => EditionStatus::Published,
        'event_date' => now()->addMonth(),
        'registration_open_at' => now()->subDay(),
        'registration_close_at' => now()->addWeek(),
    ]);
    $edition->event()->update(['status' => EventStatus::Published]);
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);

    $response = $this->postJson("/api/v1/events/{$edition->id}/preregister", [
        'event_race_id' => $race->id,
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.status', 'pending');
    expect(EventPreregistration::where('email', 'ada@example.com')->exists())->toBeTrue();
});

test('the api rejects preregistration when the edition is closed', function () {
    $edition = EventEdition::factory()->create([
        'status' => EditionStatus::Published,
        'event_date' => now()->addMonth(),
        'registration_close_at' => now()->subDay(),
    ]);
    $edition->event()->update(['status' => EventStatus::Published]);
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);

    $this->postJson("/api/v1/events/{$edition->id}/preregister", [
        'event_race_id' => $race->id,
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
    ])->assertForbidden();
});

test('a preregistration can be looked up by its token via the api', function () {
    $edition = EventEdition::factory()->create(['status' => EditionStatus::Published, 'event_date' => now()->addMonth()]);
    $edition->event()->update(['status' => EventStatus::Published]);
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);

    $preregistration = EventPreregistration::factory()->create([
        'event_edition_id' => $edition->id,
        'event_race_id' => $race->id,
        'token' => 'PRE-TESTCODE',
    ]);

    $response = $this->getJson('/api/v1/preregistrations/PRE-TESTCODE');

    $response->assertOk();
    $response->assertJsonPath('data.token', $preregistration->token);
});
