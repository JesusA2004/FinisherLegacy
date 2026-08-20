<?php

use App\Enums\AthleteIdentityStatus;
use App\Enums\AthleteMatchOutcome;
use App\Models\Athlete;
use App\Models\AthleteExternalIdentity;
use App\Models\User;
use App\Services\Athletes\AthleteIdentityMatcher;
use App\Support\Athletes\AthleteIdentityCandidateData;

beforeEach(function () {
    $this->matcher = new AthleteIdentityMatcher;
});

function candidate(array $overrides = []): AthleteIdentityCandidateData
{
    return AthleteIdentityCandidateData::fromArray(array_merge([
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
    ], $overrides));
}

test('no athletes in the system yields no match', function () {
    $result = $this->matcher->match(candidate());

    expect($result->outcome)->toBe(AthleteMatchOutcome::NoMatch);
});

test('an exact normalized email match auto-links', function () {
    $athlete = Athlete::factory()->create(['email' => 'juan@example.com', 'normalized_email' => 'juan@example.com']);

    $result = $this->matcher->match(candidate(['email' => 'JUAN@example.com']));

    expect($result->outcome)->toBe(AthleteMatchOutcome::Matched)
        ->and($result->best()->athlete->id)->toBe($athlete->id)
        ->and($result->best()->confidence)->toBe(95);
});

test('verified email plus matching birthdate scores 100 and auto-links', function () {
    $athlete = Athlete::factory()->create([
        'email' => 'juan@example.com',
        'normalized_email' => 'juan@example.com',
        'birth_date' => '1990-05-20',
    ]);

    $result = $this->matcher->match(candidate([
        'email' => 'juan@example.com',
        'email_verified' => true,
        'birth_date' => '1990-05-20',
    ]));

    expect($result->outcome)->toBe(AthleteMatchOutcome::Matched)
        ->and($result->best()->confidence)->toBe(100)
        ->and($result->best()->athlete->id)->toBe($athlete->id);
});

test('phone plus matching birthdate auto-links at 95', function () {
    $athlete = Athlete::factory()->create([
        'phone' => '+525512345678',
        'normalized_phone' => '+525512345678',
        'birth_date' => '1985-01-01',
        'email' => null,
        'normalized_email' => null,
    ]);

    $result = $this->matcher->match(candidate([
        'phone' => '+52 55 1234 5678',
        'birth_date' => '1985-01-01',
    ]));

    expect($result->outcome)->toBe(AthleteMatchOutcome::Matched)
        ->and($result->best()->athlete->id)->toBe($athlete->id);
});

test('name plus matching birthdate is a conflict, never an auto-link', function () {
    Athlete::factory()->create([
        'full_name' => 'Juan Pérez',
        'normalized_full_name' => 'juan perez',
        'birth_date' => '1990-05-20',
        'email' => null,
        'normalized_email' => null,
    ]);

    $result = $this->matcher->match(candidate(['birth_date' => '1990-05-20']));

    expect($result->outcome)->toBe(AthleteMatchOutcome::Conflict)
        ->and($result->best()->confidence)->toBe(80);
});

test('name alone never auto-merges, even with a single candidate — it always resolves to no match', function () {
    Athlete::factory()->create([
        'full_name' => 'Juan Pérez',
        'normalized_full_name' => 'juan perez',
        'email' => null,
        'normalized_email' => null,
    ]);

    $result = $this->matcher->match(candidate());

    expect($result->outcome)->toBe(AthleteMatchOutcome::NoMatch);
});

test('multiple athletes tied on email exact produce a conflict, not an arbitrary pick', function () {
    Athlete::factory()->create(['email' => 'shared@example.com', 'normalized_email' => 'shared@example.com']);
    Athlete::factory()->create(['email' => 'shared@example.com', 'normalized_email' => 'shared@example.com']);

    $result = $this->matcher->match(candidate(['email' => 'shared@example.com']));

    expect($result->outcome)->toBe(AthleteMatchOutcome::Conflict)
        ->and($result->candidates)->toHaveCount(2);
});

test('an external identity exact match wins immediately at 100 confidence, ahead of any other tier', function () {
    $athlete = Athlete::factory()->create(['email' => null, 'normalized_email' => null]);
    AthleteExternalIdentity::factory()->create([
        'athlete_id' => $athlete->id,
        'provider' => 'google',
        'provider_connection_id' => 'conn-1',
        'external_subject_id' => 'sub-123',
    ]);

    // Also create a decoy athlete matching by email exact (95) to prove the
    // external identity tier short-circuits before email is ever checked.
    Athlete::factory()->create(['email' => 'juan@example.com', 'normalized_email' => 'juan@example.com']);

    $result = $this->matcher->match(candidate([
        'email' => 'juan@example.com',
        'external_provider' => 'google',
        'external_connection_id' => 'conn-1',
        'external_subject_id' => 'sub-123',
    ]));

    expect($result->outcome)->toBe(AthleteMatchOutcome::Matched)
        ->and($result->best()->confidence)->toBe(100)
        ->and($result->best()->athlete->id)->toBe($athlete->id);
});

test('a merged athlete is never returned as a match candidate', function () {
    Athlete::factory()->create([
        'email' => 'juan@example.com',
        'normalized_email' => 'juan@example.com',
        'identity_status' => AthleteIdentityStatus::Merged,
    ]);

    $result = $this->matcher->match(candidate(['email' => 'juan@example.com']));

    expect($result->outcome)->toBe(AthleteMatchOutcome::NoMatch);
});

test('registration-context matching excludes athletes already linked to a different user', function () {
    $otherUser = User::factory()->create();
    Athlete::factory()->create([
        'email' => 'juan@example.com',
        'normalized_email' => 'juan@example.com',
        'user_id' => $otherUser->id,
    ]);

    $result = $this->matcher->match(candidate(['email' => 'juan@example.com']), excludeAthletesLinkedToOtherUsers: true);

    expect($result->outcome)->toBe(AthleteMatchOutcome::NoMatch);
});

test('registration-context matching still finds an athlete with no user at all', function () {
    $athlete = Athlete::factory()->create(['email' => 'juan@example.com', 'normalized_email' => 'juan@example.com', 'user_id' => null]);

    $result = $this->matcher->match(candidate(['email' => 'juan@example.com']), excludeAthletesLinkedToOtherUsers: true);

    expect($result->outcome)->toBe(AthleteMatchOutcome::Matched)
        ->and($result->best()->athlete->id)->toBe($athlete->id);
});
