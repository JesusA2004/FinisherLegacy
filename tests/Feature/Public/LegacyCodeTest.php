<?php

use App\Enums\LegacyCodeStatus;
use App\Models\LegacyCode;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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

// --- PERMANENCIA -----------------------------------------------------------

test('a legacy code never expires: it has no expiration column and no time-based status', function () {
    expect(Schema::hasColumn('legacy_codes', 'expires_at'))->toBeFalse();

    $statuses = array_column(LegacyCodeStatus::cases(), 'value');
    expect($statuses)->not->toContain('expired')->not->toContain('temporary');
});

test('the QR is purely derived from the code: regenerating it twice yields byte-identical SVG, with no stored file involved', function () {
    $legacyCode = LegacyCode::factory()->create(['status' => LegacyCodeStatus::Assigned]);

    $first = $this->get(route('legacy-code.qr', $legacyCode->code))->getContent();
    $second = $this->get(route('legacy-code.qr', $legacyCode->code))->getContent();

    expect($first)->toBe($second)
        ->and($first)->toContain('<svg');

    // Nothing under storage should have been written as a side effect of viewing the QR.
    expect(Storage::disk('public')->allFiles())
        ->each(fn ($file) => expect($file->value)->not->toContain($legacyCode->code));
});

test('the legacy code stays stable across unrelated user identity changes', function () {
    $user = User::factory()->create(['email' => 'original@example.com']);
    $legacyCode = LegacyCode::factory()->create(['status' => LegacyCodeStatus::Claimed, 'user_id' => $user->id, 'claimed_by_user_id' => $user->id]);
    $originalCode = $legacyCode->code;

    $user->update(['email' => 'changed@example.com', 'name' => 'Nuevo Nombre']);

    expect($legacyCode->fresh()->code)->toBe($originalCode);
    $this->get(route('legacy-code.show', $originalCode))->assertOk();
});
