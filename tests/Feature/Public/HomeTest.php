<?php

use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Models\EventEdition;

test('home page loads', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Home'));
});

test('home page only features published, upcoming editions', function () {
    $published = EventEdition::factory()->create([
        'event_date' => now()->addWeek(),
        'status' => EditionStatus::Published,
    ]);
    $published->event()->update(['status' => EventStatus::Published]);

    $draftEdition = EventEdition::factory()->create([
        'event_date' => now()->addWeek(),
        'status' => EditionStatus::Draft,
    ]);
    $draftEdition->event()->update(['status' => EventStatus::Published]);

    $response = $this->get(route('home'));

    $response->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('featuredEditions.0.id', $published->id)
        ->has('featuredEditions', 1)
    );
});
