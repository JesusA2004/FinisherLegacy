<?php

use App\Enums\LegacyCodeStatus;
use App\Models\LegacyCode;
use App\Models\Medal;
use App\Models\Plate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function medalPayload(array $overrides = []): array
{
    return array_merge([
        'origin' => 'manual',
        'event_name_manual' => 'Maratón de Prueba',
        'event_date' => '2026-01-15',
        'city' => 'CDMX',
        'country' => 'México',
        'distance_label' => '21K',
        'official_time' => '01:55:10',
        'pace' => '5:30',
        'story' => 'Una carrera inolvidable.',
        'visibility' => 'public',
        'front_image' => UploadedFile::fake()->image('front.jpg', 800, 800),
    ], $overrides);
}

test('an athlete can create a manual medal with a front image', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('dashboard.medals.store'), medalPayload());

    $response->assertRedirect();
    $medal = Medal::where('user_id', $user->id)->firstOrFail();
    expect($medal->title)->toBe('Maratón de Prueba');
    expect($medal->images()->where('type', 'front')->exists())->toBeTrue();
});

test('an athlete can upload a back image alongside the front image', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('dashboard.medals.store'), medalPayload([
        'back_image' => UploadedFile::fake()->image('back.jpg', 800, 800),
    ]));

    $medal = Medal::where('user_id', $user->id)->firstOrFail();
    expect($medal->images()->where('type', 'back')->exists())->toBeTrue();
});

test('a medal requires a front image', function () {
    $user = User::factory()->create();
    $payload = medalPayload();
    unset($payload['front_image']);

    $response = $this->actingAs($user)->post(route('dashboard.medals.store'), $payload);

    $response->assertSessionHasErrors('front_image');
});

test('invalid files are rejected for medal images', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('dashboard.medals.store'), medalPayload([
        'front_image' => UploadedFile::fake()->create('document.pdf', 200, 'application/pdf'),
    ]));

    $response->assertSessionHasErrors('front_image');
});

test('an athlete can edit their own medal', function () {
    $user = User::factory()->create();
    $medal = Medal::factory()->for($user)->create(['title' => 'Original']);

    $response = $this->actingAs($user)->put(route('dashboard.medals.update', $medal), [
        'event_name_manual' => 'Título Actualizado',
        'story' => 'Historia actualizada.',
        'visibility' => 'private',
    ]);

    $response->assertRedirect();
    expect($medal->fresh()->title)->toBe('Título Actualizado');
});

test('an athlete cannot edit a medal that belongs to someone else', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $medal = Medal::factory()->for($owner)->create();

    $response = $this->actingAs($intruder)->put(route('dashboard.medals.update', $medal), [
        'story' => 'intento no autorizado',
        'visibility' => 'public',
    ]);

    $response->assertForbidden();
});

test('an athlete cannot view a medal that belongs to someone else', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $medal = Medal::factory()->for($owner)->create();

    $response = $this->actingAs($intruder)->get(route('dashboard.medals.show', $medal));

    $response->assertForbidden();
});

test('an athlete can archive their own medal', function () {
    $user = User::factory()->create();
    $medal = Medal::factory()->for($user)->create();

    $response = $this->actingAs($user)->delete(route('dashboard.medals.destroy', $medal));

    $response->assertRedirect(route('dashboard.medals.index'));
    expect(Medal::find($medal->id))->toBeNull();
    expect(Medal::withTrashed()->find($medal->id))->not->toBeNull();
});

test('archiving a medal linked to a plate keeps the historical link intact', function () {
    $user = User::factory()->create();
    $medal = Medal::factory()->for($user)->create();
    $plate = Plate::factory()->create(['medal_id' => $medal->id, 'user_id' => $user->id]);
    $legacyCode = LegacyCode::factory()->create([
        'medal_id' => $medal->id,
        'user_id' => $user->id,
        'status' => LegacyCodeStatus::Claimed,
    ]);

    $this->actingAs($user)->delete(route('dashboard.medals.destroy', $medal));

    expect(Medal::withTrashed()->find($medal->id))->not->toBeNull();
    expect($plate->fresh()->medal_id)->toBe($medal->id);
    expect($legacyCode->fresh()->medal_id)->toBe($medal->id);
});
