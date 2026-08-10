<?php

use App\Enums\LegacyCodeStatus;
use App\Enums\PlateGenerationMode;
use App\Models\LegacyCode;
use App\Models\Medal;
use App\Models\Plate;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function apiAssignedLegacyCode(array $plateAttributes = [], array $legacyCodeAttributes = []): LegacyCode
{
    $plate = Plate::factory()->create(array_merge([
        'generation_mode' => PlateGenerationMode::Integrated,
        'event_name' => 'Maratón API',
        'official_time' => '02:10:00',
    ], $plateAttributes));

    $legacyCode = LegacyCode::factory()->create(array_merge([
        'plate_id' => $plate->id,
        'status' => LegacyCodeStatus::Assigned,
    ], $legacyCodeAttributes));

    $plate->update(['legacy_code_id' => $legacyCode->id]);

    return $legacyCode;
}

test('anyone can look up a legacy code', function () {
    $legacyCode = apiAssignedLegacyCode();

    $response = $this->getJson("/api/v1/legacy-codes/{$legacyCode->code}");

    $response->assertOk();
    $response->assertJsonPath('data.available', true);
    $response->assertJsonPath('data.plate.event_name', 'Maratón API');
});

test('an unknown legacy code returns 404 via the api', function () {
    $this->getJson('/api/v1/legacy-codes/DOESNOTEXIST')->assertNotFound();
});

test('claiming via the api requires authentication', function () {
    $legacyCode = apiAssignedLegacyCode();

    $this->postJson("/api/v1/legacy-codes/{$legacyCode->code}/claim")->assertUnauthorized();
});

test('claiming via the api links the plate and creates a medal', function () {
    $user = User::factory()->create();
    $legacyCode = apiAssignedLegacyCode();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/legacy-codes/{$legacyCode->code}/claim");

    $response->assertOk();
    expect($response->json('data.medal.id'))->not->toBeNull();
    expect(Medal::where('user_id', $user->id)->count())->toBe(1);
});

test('claiming a code already owned by someone else returns 409 via the api', function () {
    $owner = User::factory()->create();
    $legacyCode = apiAssignedLegacyCode();

    Sanctum::actingAs($owner);
    $this->postJson("/api/v1/legacy-codes/{$legacyCode->code}/claim")->assertOk();
    expect($legacyCode->fresh()->claimed_by_user_id)->toBe($owner->id);

    $other = User::factory()->create();
    Sanctum::actingAs($other);

    $this->postJson("/api/v1/legacy-codes/{$legacyCode->code}/claim")
        ->assertStatus(409);
});

test('claiming a blocked legacy code returns 403 via the api', function () {
    $user = User::factory()->create();
    $legacyCode = apiAssignedLegacyCode([], ['status' => LegacyCodeStatus::Blocked]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/legacy-codes/{$legacyCode->code}/claim")
        ->assertStatus(403);
});
