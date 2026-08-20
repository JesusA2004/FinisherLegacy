<?php

use App\Actions\Athletes\ResolveAthleteIdentity;
use App\Enums\AthleteIdentityConflictStatus;
use App\Enums\ResolveAthleteIdentityStatus;
use App\Models\Athlete;
use App\Models\AthleteIdentityConflict;
use App\Support\Athletes\AthleteIdentityCandidateData;

beforeEach(function () {
    $this->resolve = app(ResolveAthleteIdentity::class);
});

function identityData(array $overrides = []): AthleteIdentityCandidateData
{
    return AthleteIdentityCandidateData::fromArray(array_merge([
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
    ], $overrides));
}

test('no existing candidate creates a brand new athlete', function () {
    $result = $this->resolve->handle(identityData(['email' => 'juan@example.com']), 'import');

    expect($result->status)->toBe(ResolveAthleteIdentityStatus::Created)
        ->and($result->athlete)->not->toBeNull()
        ->and(Athlete::count())->toBe(1);
});

test('resolving the same email twice matches the same athlete instead of creating a second one', function () {
    $first = $this->resolve->handle(identityData(['email' => 'juan@example.com']), 'import');
    $second = $this->resolve->handle(identityData(['email' => 'juan@example.com']), 'import');

    expect($first->athlete->id)->toBe($second->athlete->id)
        ->and($second->status)->toBe(ResolveAthleteIdentityStatus::Matched)
        ->and(Athlete::count())->toBe(1);
});

test('an ambiguous name-and-birthdate match records a pending conflict and creates no athlete', function () {
    $this->resolve->handle(identityData(['email' => 'existing@example.com', 'birth_date' => '1990-05-20']), 'import');
    // Force a NameAndBirthdate collision against a *different* email so the
    // higher-confidence email tier never fires — only the ambiguous one does.
    Athlete::factory()->create([
        'full_name' => 'Juan Pérez',
        'normalized_full_name' => 'juan perez',
        'birth_date' => '1990-05-20',
        'email' => null,
        'normalized_email' => null,
    ]);

    $athletesBefore = Athlete::count();

    $result = $this->resolve->handle(identityData(['birth_date' => '1990-05-20']), 'import');

    expect($result->status)->toBe(ResolveAthleteIdentityStatus::Conflict)
        ->and($result->conflict)->not->toBeNull()
        ->and($result->conflict->status)->toBe(AthleteIdentityConflictStatus::Pending)
        ->and(Athlete::count())->toBe($athletesBefore)
        ->and(AthleteIdentityConflict::count())->toBe(1);
});

// --- CRITERIO FINAL (§125): 3 eventos, 3 bibs, 1 solo Athlete -------------

test('the same person imported under three different bibs across three different events resolves to exactly one athlete', function () {
    $eventA = $this->resolve->handle(identityData(['email' => 'juan.perez@example.com']), 'import', 'event-a-142');
    $eventB = $this->resolve->handle(identityData(['email' => 'juan.perez@example.com']), 'import', 'event-b-992');
    $eventC = $this->resolve->handle(identityData(['email' => 'juan.perez@example.com']), 'import', 'event-c-31');

    expect($eventA->athlete->id)->toBe($eventB->athlete->id)
        ->and($eventB->athlete->id)->toBe($eventC->athlete->id)
        ->and(Athlete::count())->toBe(1);
});
