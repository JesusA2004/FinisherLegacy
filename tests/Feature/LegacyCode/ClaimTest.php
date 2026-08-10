<?php

use App\Enums\LegacyCodeStatus;
use App\Enums\PlateGenerationMode;
use App\Models\LegacyCode;
use App\Models\Medal;
use App\Models\Plate;
use App\Models\User;

function createAssignedLegacyCode(array $plateAttributes = [], array $legacyCodeAttributes = []): LegacyCode
{
    $plate = Plate::factory()->create(array_merge([
        'generation_mode' => PlateGenerationMode::Integrated,
        'event_name' => 'Maratón de Prueba',
        'race_name' => '21K',
        'official_time' => '01:45:10',
        'pace' => '5:00',
    ], $plateAttributes));

    $legacyCode = LegacyCode::factory()->create(array_merge([
        'plate_id' => $plate->id,
        'status' => LegacyCodeStatus::Assigned,
    ], $legacyCodeAttributes));

    $plate->update(['legacy_code_id' => $legacyCode->id]);

    return $legacyCode;
}

test('a guest is redirected to login when claiming', function () {
    $legacyCode = createAssignedLegacyCode();

    $response = $this->post(route('legacy-code.claim', $legacyCode->code));

    $response->assertRedirect(route('login'));
    expect($legacyCode->fresh()->claimed_by_user_id)->toBeNull();
});

test('claiming an unclaimed integrated plate links it and creates a medal', function () {
    $user = User::factory()->create();
    $legacyCode = createAssignedLegacyCode();
    $plate = $legacyCode->plate;

    $response = $this->actingAs($user)->post(route('legacy-code.claim', $legacyCode->code));

    $legacyCode->refresh();
    $plate->refresh();

    expect($legacyCode->status)->toBe(LegacyCodeStatus::Claimed)
        ->and($legacyCode->claimed_by_user_id)->toBe($user->id)
        ->and($legacyCode->user_id)->toBe($user->id)
        ->and($legacyCode->claimed_at)->not->toBeNull()
        ->and($plate->user_id)->toBe($user->id)
        ->and($plate->linked_at)->not->toBeNull()
        ->and($plate->medal_id)->not->toBeNull();

    $medal = Medal::findOrFail($plate->medal_id);

    expect($medal->user_id)->toBe($user->id)
        ->and($medal->title)->toBe('Maratón de Prueba')
        ->and($medal->distance_label)->toBe('21K')
        ->and($medal->official_time)->toBe('01:45:10')
        ->and($medal->pace)->toBe('5:00')
        ->and($medal->visibility->value)->toBe('public');

    $response->assertRedirect(route('dashboard.medals.show', $medal));
});

test('claiming a quick plate with no participant still avoids asking for time/pace', function () {
    $user = User::factory()->create();
    $legacyCode = createAssignedLegacyCode([
        'generation_mode' => PlateGenerationMode::Quick,
        'event_name' => 'Carrera Nocturna',
        'official_time' => '00:55:00',
    ]);

    $this->actingAs($user)->post(route('legacy-code.claim', $legacyCode->code));

    $medal = Medal::where('user_id', $user->id)->firstOrFail();

    expect($medal->official_time)->toBe('00:55:00')
        ->and($medal->event_participant_id)->toBeNull();
});

test('claiming the same code twice as the same user is idempotent', function () {
    $user = User::factory()->create();
    $legacyCode = createAssignedLegacyCode();

    $this->actingAs($user)->post(route('legacy-code.claim', $legacyCode->code));
    $firstMedalCount = Medal::count();

    $response = $this->actingAs($user)->post(route('legacy-code.claim', $legacyCode->code));

    expect(Medal::count())->toBe($firstMedalCount);
    $response->assertRedirect();
});

test('claiming a code already owned by someone else returns a conflict without exposing the owner', function () {
    $owner = User::factory()->create();
    $legacyCode = createAssignedLegacyCode();
    $this->actingAs($owner)->post(route('legacy-code.claim', $legacyCode->code));

    $otherUser = User::factory()->create();
    $response = $this->actingAs($otherUser)->post(route('legacy-code.claim', $legacyCode->code));

    $legacyCode->refresh();

    expect($legacyCode->claimed_by_user_id)->toBe($owner->id);
    $response->assertSessionHas('inertia.flash_data.toast.type', 'error');
});

test('a blocked legacy code cannot be claimed', function () {
    $user = User::factory()->create();
    $legacyCode = createAssignedLegacyCode([], ['status' => LegacyCodeStatus::Blocked]);

    $response = $this->actingAs($user)->post(route('legacy-code.claim', $legacyCode->code));

    expect($legacyCode->fresh()->claimed_by_user_id)->toBeNull();
    $response->assertSessionHas('inertia.flash_data.toast.type', 'error');
});

test('claiming an unknown legacy code returns 404', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/l/DOESNOTEXIST/claim');

    $response->assertNotFound();
});
