<?php

use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventEdition;

test('the events index returns only published, non-finished editions by default', function () {
    $upcoming = EventEdition::factory()->create(['status' => EditionStatus::Published, 'event_date' => now()->addMonth()]);
    $upcoming->event()->update(['status' => EventStatus::Published]);

    $finished = EventEdition::factory()->create(['status' => EditionStatus::Published, 'event_date' => now()->subMonth()]);
    $finished->event()->update(['status' => EventStatus::Published]);

    $response = $this->getJson('/api/v1/events');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.event.slug', $upcoming->event->slug);
});

test('the event detail endpoint returns 404 for a draft event', function () {
    $event = Event::factory()->create(['status' => EventStatus::Draft]);

    $this->getJson("/api/v1/events/{$event->slug}")->assertNotFound();
});

test('the event detail endpoint returns the current edition for a published event', function () {
    $edition = EventEdition::factory()->create(['status' => EditionStatus::Published, 'event_date' => now()->addMonth()]);
    $edition->event()->update(['status' => EventStatus::Published]);

    $response = $this->getJson("/api/v1/events/{$edition->event->slug}");

    $response->assertOk();
    $response->assertJsonPath('data.slug', $edition->event->slug);
    $response->assertJsonPath('data.edition.name', $edition->name);
});
