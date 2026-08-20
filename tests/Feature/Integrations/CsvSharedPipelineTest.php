<?php

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Enums\ProviderConnectionStatus;
use App\Imports\ParticipantsImport;
use App\Models\Athlete;
use App\Models\EventEdition;
use App\Models\EventImport;
use App\Models\EventParticipant;
use App\Models\EventRace;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\Integrations\EventIngestionService;
use App\Support\Integrations\ExternalParticipantData;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The point of docs/adr/0005 §53-56, §114: CSV and an API sync are two
 * adapters feeding the exact same identity pipeline — a CSV row and a
 * provider participant with a strong-enough shared signal (email) must
 * resolve to the same Athlete, with no special-casing for either source.
 */
test('a CSV row and a mock API participant with the same email resolve to one Athlete', function () {
    $edition = EventEdition::factory()->create();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id, 'name' => '42K']);
    $user = User::factory()->create();

    $import = EventImport::create([
        'event_edition_id' => $edition->id,
        'type' => ImportType::Participants,
        'file_path' => 'imports/test.csv',
        'original_filename' => 'test.csv',
        'column_mapping' => ['bib_number' => 0, 'first_name' => 1, 'last_name' => 2, 'race_name' => 3, 'email' => 4],
        'status' => ImportStatus::Pending,
        'total_rows' => 1,
        'created_by' => $user->id,
    ]);

    (new ParticipantsImport($import->id))->collection(new Collection([
        new Collection(['501', 'Zuriel', 'Ávila', '42K', 'zuriel@shared.test']),
    ]));

    $csvParticipant = EventParticipant::where('event_edition_id', $edition->id)->where('bib_number', '501')->firstOrFail();
    expect($csvParticipant->athlete_id)->not->toBeNull();

    $connection = ProviderConnection::create([
        'uuid' => (string) Str::uuid(),
        'provider_key' => 'mock',
        'name' => 'Mock',
        'status' => ProviderConnectionStatus::Untested,
        'settings' => [],
    ]);

    $apiParticipant = app(EventIngestionService::class)->ingestParticipant(
        ExternalParticipantData::fromArray([
            'external_participant_id' => 'P-501', 'bib_number' => '9501',
            'first_name' => 'Zuriel', 'last_name' => 'Ávila', 'email' => 'zuriel@shared.test',
        ]),
        $edition, $race, $connection, 'api',
    );

    expect($apiParticipant->athlete_id)->toBe($csvParticipant->athlete_id)
        ->and(Athlete::count())->toBe(1);
});
