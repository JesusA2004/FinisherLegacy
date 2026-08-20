<?php

use App\Actions\Athletes\MergeAthletes;
use App\Enums\AthleteIdentityStatus;
use App\Exceptions\Athletes\AthleteMergeUserConflictException;
use App\Models\Athlete;
use App\Models\AthleteExternalIdentity;
use App\Models\EventParticipant;
use App\Models\Medal;
use App\Models\Plate;
use App\Models\User;

beforeEach(function () {
    $this->merge = app(MergeAthletes::class);
    $this->actor = User::factory()->create();
});

test('merging moves participants, plates, and medals from source to target and retires the source', function () {
    $source = Athlete::factory()->create();
    $target = Athlete::factory()->create();
    $participant = EventParticipant::factory()->create(['athlete_id' => $source->id]);
    $plate = Plate::factory()->create(['athlete_id' => $source->id]);
    $medal = Medal::factory()->create(['athlete_id' => $source->id, 'user_id' => User::factory()->create()->id]);

    $result = $this->merge->handle($source, $target, $this->actor, 'duplicate confirmed by admin');

    expect($result->id)->toBe($target->id)
        ->and($participant->fresh()->athlete_id)->toBe($target->id)
        ->and($plate->fresh()->athlete_id)->toBe($target->id)
        ->and($medal->fresh()->athlete_id)->toBe($target->id)
        ->and($source->fresh()->identity_status)->toBe(AthleteIdentityStatus::Merged)
        ->and($source->fresh()->merged_into_athlete_id)->toBe($target->id);
});

test('merging is blocked when source and target are each already linked to a different user', function () {
    $source = Athlete::factory()->create(['user_id' => User::factory()->create()->id]);
    $target = Athlete::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->merge->handle($source, $target, $this->actor, 'attempted');
})->throws(AthleteMergeUserConflictException::class);

test('merging moves the source user onto the target when only the source has one', function () {
    $user = User::factory()->create();
    $source = Athlete::factory()->create(['user_id' => $user->id]);
    $target = Athlete::factory()->create(['user_id' => null]);

    $this->merge->handle($source, $target, $this->actor, 'confirmed same person');

    expect($target->fresh()->user_id)->toBe($user->id)
        ->and($source->fresh()->user_id)->toBeNull();
});

test('merging a source into itself is rejected', function () {
    $athlete = Athlete::factory()->create();

    $this->merge->handle($athlete, $athlete, $this->actor, 'nonsense');
})->throws(\Illuminate\Validation\ValidationException::class);

test('merging moves an external identity onto the target when the target has no conflicting one', function () {
    $source = Athlete::factory()->create();
    $target = Athlete::factory()->create();

    $identity = AthleteExternalIdentity::factory()->create([
        'athlete_id' => $source->id,
        'provider' => 'google',
        'provider_connection_id' => 'conn-1',
        'external_subject_id' => 'sub-1',
    ]);

    $this->merge->handle($source, $target, $this->actor, 'confirmed');

    expect($identity->fresh()->athlete_id)->toBe($target->id);
});

test('a retired source athlete is never hard-deleted, so historical references stay valid', function () {
    $source = Athlete::factory()->create();
    $target = Athlete::factory()->create();

    $this->merge->handle($source, $target, $this->actor, 'confirmed');

    expect(Athlete::find($source->id))->not->toBeNull();
});
