<?php

use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventEdition;

test('events index loads', function () {
    $response = $this->get(route('events.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('events/Index'));
});

test('only published events appear in the events index', function () {
    $published = EventEdition::factory()->create(['status' => EditionStatus::Published]);
    $published->event()->update(['status' => EventStatus::Published]);

    $draft = EventEdition::factory()->create(['status' => EditionStatus::Published]);
    $draft->event()->update(['status' => EventStatus::Draft]);

    $response = $this->get(route('events.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('events/Index')
        ->has('editions.data', 1)
        ->where('editions.data.0.event.slug', $published->event->slug)
    );
});

test('a published event detail page loads for a valid slug', function () {
    $edition = EventEdition::factory()->create(['status' => EditionStatus::Published]);
    $edition->event()->update(['status' => EventStatus::Published]);

    $response = $this->get(route('events.show', $edition->event));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('events/Show')
        ->where('event.slug', $edition->event->slug)
    );
});

test('an unknown event slug returns 404', function () {
    $response = $this->get('/events/does-not-exist');

    $response->assertNotFound();
});

test('a draft event returns 404 publicly', function () {
    $event = Event::factory()->create(['status' => EventStatus::Draft]);

    $response = $this->get(route('events.show', $event));

    $response->assertNotFound();
});
