<?php

use App\Enums\LegacyCodeStatus;
use App\Models\LegacyCode;

test('a valid legacy code page loads', function () {
    $legacyCode = LegacyCode::factory()->create(['status' => LegacyCodeStatus::Generated]);

    $response = $this->get(route('legacy-code.show', $legacyCode->code));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('legacy-code/Show')
        ->where('code', $legacyCode->code)
        ->where('available', true)
    );
});

test('an unknown legacy code returns 404', function () {
    $response = $this->get('/l/DOESNOTEXIST');

    $response->assertNotFound();
});

test('a blocked legacy code hides plate details', function () {
    $legacyCode = LegacyCode::factory()->create(['status' => LegacyCodeStatus::Blocked]);

    $response = $this->get(route('legacy-code.show', $legacyCode->code));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('legacy-code/Show')
        ->where('available', false)
        ->where('plate', null)
    );
});
