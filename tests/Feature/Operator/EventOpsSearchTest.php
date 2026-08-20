<?php

use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\EventRace;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->operator = User::factory()->create();
    $this->operator->assignRole('event_operator');

    $this->edition = EventEdition::factory()->create();
    $this->race = EventRace::factory()->create(['event_edition_id' => $this->edition->id]);

    $this->actingAs($this->operator)->post(route('operator.select-event'), ['event_edition_id' => $this->edition->id]);
});

test('an exact bib match is returned even when a name also matches loosely', function () {
    EventParticipant::factory()->create([
        'event_edition_id' => $this->edition->id, 'event_race_id' => $this->race->id,
        'bib_number' => '1234', 'first_name' => 'Ana', 'last_name' => 'Ruiz', 'full_name' => 'Ana Ruiz',
    ]);
    EventParticipant::factory()->create([
        'event_edition_id' => $this->edition->id, 'event_race_id' => $this->race->id,
        'bib_number' => '999', 'first_name' => '1234', 'last_name' => 'Something', 'full_name' => '1234 Something',
    ]);

    $response = $this->actingAs($this->operator)->getJson(route('operator.search', ['q' => '1234']));

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('bib_number'))->toContain('1234');
});

test('two homonyms are both returned, never auto-selected', function () {
    EventParticipant::factory()->create([
        'event_edition_id' => $this->edition->id, 'event_race_id' => $this->race->id,
        'full_name' => 'Juan Pérez', 'first_name' => 'Juan', 'last_name' => 'Pérez',
    ]);
    EventParticipant::factory()->create([
        'event_edition_id' => $this->edition->id, 'event_race_id' => $this->race->id,
        'full_name' => 'Juan Pérez', 'first_name' => 'Juan', 'last_name' => 'Pérez',
    ]);

    $response = $this->actingAs($this->operator)->getJson(route('operator.search', ['q' => 'Juan Pérez']));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('search never returns participants from a different edition', function () {
    $otherEdition = EventEdition::factory()->create();
    $otherRace = EventRace::factory()->create(['event_edition_id' => $otherEdition->id]);
    EventParticipant::factory()->create([
        'event_edition_id' => $otherEdition->id, 'event_race_id' => $otherRace->id,
        'bib_number' => '5555', 'full_name' => 'Otro Evento',
    ]);

    $response = $this->actingAs($this->operator)->getJson(route('operator.search', ['q' => '5555']));

    $response->assertOk();
    expect($response->json('data'))->toBe([]);
});
