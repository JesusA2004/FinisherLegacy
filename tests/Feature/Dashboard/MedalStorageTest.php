<?php

use App\Models\AthleteProfile;
use App\Models\Medal;
use App\Models\MedalImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function medalStoragePayload(array $overrides = []): array
{
    return array_merge([
        'origin' => 'manual',
        'event_name_manual' => 'Maratón de Prueba',
        'event_date' => '2026-01-15',
        'visibility' => 'public',
        'front_image' => UploadedFile::fake()->image('front.jpg', 2000, 2000),
    ], $overrides);
}

test('uploaded medal images are optimized and thumbnailed, not stored raw', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('dashboard.medals.store'), medalStoragePayload());

    $image = MedalImage::where('type', 'front')->firstOrFail();

    expect($image->optimized_path)->toContain('/display/')
        ->and($image->thumbnail_path)->toContain('/thumbnails/')
        ->and($image->original_path)->toContain('/original/');

    Storage::disk('public')->assertExists($image->optimized_path);
    Storage::disk('public')->assertExists($image->thumbnail_path);
});

test('a medal can be created with gallery images up to the configured limit', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('dashboard.medals.store'), medalStoragePayload([
        'gallery_images' => [
            UploadedFile::fake()->image('g1.jpg'),
            UploadedFile::fake()->image('g2.jpg'),
            UploadedFile::fake()->image('g3.jpg'),
        ],
    ]));

    $response->assertRedirect();
    $medal = Medal::where('user_id', $user->id)->firstOrFail();
    expect($medal->images()->where('type', 'gallery')->count())->toBe(3);
});

test('more gallery images than the configured max are rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('dashboard.medals.store'), medalStoragePayload([
        'gallery_images' => [
            UploadedFile::fake()->image('g1.jpg'),
            UploadedFile::fake()->image('g2.jpg'),
            UploadedFile::fake()->image('g3.jpg'),
            UploadedFile::fake()->image('g4.jpg'),
        ],
    ]));

    $response->assertSessionHasErrors('gallery_images');
});

test('a medal cannot exceed the configured total image limit across updates', function () {
    config(['finisher.medal.max_images_per_medal' => 3]);

    $user = User::factory()->create();
    $medal = Medal::factory()->for($user)->create();
    MedalImage::factory()->count(3)->for($medal)->create(['type' => 'gallery']);

    $response = $this->actingAs($user)->put(route('dashboard.medals.update', $medal), [
        'visibility' => 'public',
        'gallery_images' => [UploadedFile::fake()->image('extra.jpg')],
    ]);

    $response->assertSessionHasErrors('gallery_images');
});

test('reaching the medal quota shows a friendly message instead of a server error', function () {
    config(['finisher.quotas.max_medals_per_athlete' => 1]);

    $user = User::factory()->create();
    Medal::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('dashboard.medals.store'), medalStoragePayload());

    $response->assertSessionHasErrors('quota');
    expect(Medal::where('user_id', $user->id)->count())->toBe(1);
});

test('reaching the image quota shows a friendly message instead of a server error', function () {
    config(['finisher.quotas.max_images_per_athlete' => 1]);

    $user = User::factory()->create();
    $existingMedal = Medal::factory()->for($user)->create();
    MedalImage::factory()->for($existingMedal)->create(['type' => 'front']);

    $response = $this->actingAs($user)->post(route('dashboard.medals.store'), medalStoragePayload());

    $response->assertSessionHasErrors('quota');
});

test('deleting a gallery image removes its files from disk', function () {
    $user = User::factory()->create();
    $medal = Medal::factory()->for($user)->create();

    $this->actingAs($user)->put(route('dashboard.medals.update', $medal), [
        'visibility' => 'public',
        'gallery_images' => [UploadedFile::fake()->image('g1.jpg')],
    ]);

    $galleryImage = $medal->images()->where('type', 'gallery')->firstOrFail();
    $path = $galleryImage->optimized_path;
    Storage::disk('public')->assertExists($path);

    $this->actingAs($user)->delete(route('dashboard.medals.gallery.destroy', [$medal, $galleryImage]));

    Storage::disk('public')->assertMissing($path);
    expect(MedalImage::find($galleryImage->id))->toBeNull();
});

test('replacing a profile avatar deletes the old derived file', function () {
    $user = User::factory()->create();
    $profile = AthleteProfile::factory()->for($user)->create();

    $this->actingAs($user)->patch(route('dashboard.profile.update'), [
        'username' => $profile->username,
        'profile_visibility' => 'public',
        'profile_photo' => UploadedFile::fake()->image('avatar1.jpg', 1000, 1000),
    ]);

    $firstPath = $profile->fresh()->profile_photo_path;
    Storage::disk('public')->assertExists($firstPath);

    $this->actingAs($user)->patch(route('dashboard.profile.update'), [
        'username' => $profile->username,
        'profile_visibility' => 'public',
        'profile_photo' => UploadedFile::fake()->image('avatar2.jpg', 1000, 1000),
    ]);

    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($profile->fresh()->profile_photo_path);
});

test('video uploads are disabled by default for the pilot', function () {
    expect(config('finisher.video.enabled'))->toBeFalse();
});
