<?php

use App\Models\LegacyId;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard shows the athlete legacy id', function () {
    $user = User::factory()->create();
    $legacyId = LegacyId::create([
        'user_id' => $user->id,
        'code' => 'FL-TESTCODE',
        'status' => 'active',
        'issued_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('legacyId', $legacyId->code)
    );
});
