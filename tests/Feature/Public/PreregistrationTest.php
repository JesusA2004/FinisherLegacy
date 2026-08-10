<?php

use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Models\EventEdition;
use App\Models\EventPreregistration;
use App\Models\EventRace;
use App\Models\User;

function publishedEditionWithRace(array $editionAttributes = []): EventEdition
{
    $edition = EventEdition::factory()->create(array_merge([
        'status' => EditionStatus::Published,
        'event_date' => now()->addMonth(),
        'registration_open_at' => now()->subDay(),
        'registration_close_at' => now()->addWeek(),
    ], $editionAttributes));
    $edition->event()->update(['status' => EventStatus::Published]);
    EventRace::factory()->create(['event_edition_id' => $edition->id, 'name' => '10K']);

    return $edition->fresh();
}

test('the preregister form shows open races for a published edition', function () {
    $edition = publishedEditionWithRace();

    $response = $this->get(route('events.preregister', $edition->event));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('events/Preregister')
        ->where('isOpen', true)
    );
});

test('a guest can preregister without an account', function () {
    $edition = publishedEditionWithRace();
    $race = $edition->races->first();

    $response = $this->post(route('preregistrations.store', $edition->event), [
        'event_race_id' => $race->id,
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'phone' => '5555555555',
    ]);

    $preregistration = EventPreregistration::where('email', 'ada@example.com')->firstOrFail();
    expect($preregistration->user_id)->toBeNull()
        ->and($preregistration->token)->toStartWith('PRE-')
        ->and($preregistration->status->value)->toBe('pending');

    $response->assertRedirect(route('preregistrations.show', $preregistration->token));
});

test('an authenticated user preregistering is linked to their account', function () {
    $user = User::factory()->create();
    $edition = publishedEditionWithRace();
    $race = $edition->races->first();

    $this->actingAs($user)->post(route('preregistrations.store', $edition->event), [
        'event_race_id' => $race->id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
    ]);

    $preregistration = EventPreregistration::where('user_id', $user->id)->firstOrFail();
    expect($preregistration->email)->toBe($user->email);
});

test('preregistration is rejected when registration is closed', function () {
    $edition = publishedEditionWithRace(['registration_close_at' => now()->subDay()]);
    $race = $edition->races->first();

    $response = $this->post(route('preregistrations.store', $edition->event), [
        'event_race_id' => $race->id,
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
    ]);

    $response->assertForbidden();
    expect(EventPreregistration::count())->toBe(0);
});

test('the confirmation page shows the preregistration status and a real qr', function () {
    $edition = publishedEditionWithRace();
    $race = $edition->races->first();

    $this->post(route('preregistrations.store', $edition->event), [
        'event_race_id' => $race->id,
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
    ]);

    $preregistration = EventPreregistration::firstOrFail();

    $response = $this->get(route('preregistrations.show', $preregistration->token));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('preregistrations/Show')
        ->where('token', $preregistration->token)
    );

    $qrResponse = $this->get(route('preregistrations.qr', $preregistration->token));
    $qrResponse->assertOk();
    $qrResponse->assertHeader('Content-Type', 'image/svg+xml');
});
