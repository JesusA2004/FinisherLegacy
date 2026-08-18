<?php

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Imports\ParticipantsImport;
use App\Models\EventEdition;
use App\Models\EventImport;
use App\Models\EventParticipant;
use App\Models\EventRace;
use App\Models\User;
use Illuminate\Support\Collection;

function makeImport(EventEdition $edition, array $mapping, User $user): EventImport
{
    return EventImport::create([
        'event_edition_id' => $edition->id,
        'type' => ImportType::Participants,
        'file_path' => 'imports/test.csv',
        'original_filename' => 'test.csv',
        'column_mapping' => $mapping,
        'status' => ImportStatus::Pending,
        'total_rows' => 1,
        'created_by' => $user->id,
    ]);
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->edition = EventEdition::factory()->create();
    $this->race = EventRace::factory()->create([
        'event_edition_id' => $this->edition->id,
        'name' => '42K',
    ]);
});

test('a roster-only import with no time columns mapped never creates a result', function () {
    $mapping = [
        'bib_number' => 0, 'first_name' => 1, 'last_name' => 2, 'race_name' => 3,
    ];
    $import = makeImport($this->edition, $mapping, $this->user);

    $rows = new Collection([
        new Collection(['101', 'Ana', 'Torres', '42K']),
    ]);

    (new ParticipantsImport($import->id))->collection($rows);

    $participant = EventParticipant::where('bib_number', '101')->firstOrFail();
    expect($participant->result)->toBeNull();
    expect($import->fresh()->successful_rows)->toBe(1);
});

test('official_time and pace columns create a result when mapped', function () {
    $mapping = [
        'bib_number' => 0, 'first_name' => 1, 'last_name' => 2, 'race_name' => 3,
        'official_time' => 4, 'pace' => 5,
    ];
    $import = makeImport($this->edition, $mapping, $this->user);

    $rows = new Collection([
        new Collection(['102', 'Luis', 'Pérez', '42K', '03:45:10', '5:20']),
    ]);

    (new ParticipantsImport($import->id))->collection($rows);

    $participant = EventParticipant::where('bib_number', '102')->firstOrFail();
    expect($participant->result)->not->toBeNull()
        ->and($participant->result->official_time)->toBe('03:45:10')
        ->and($participant->result->pace)->toBe('5:20');
});

test('mapped split columns create labeled splits without requiring official_time', function () {
    $mapping = [
        'bib_number' => 0, 'first_name' => 1, 'last_name' => 2, 'race_name' => 3,
        'splits' => [
            ['label' => '5K', 'column' => 4],
            ['label' => '10K', 'column' => 5],
        ],
    ];
    $import = makeImport($this->edition, $mapping, $this->user);

    $rows = new Collection([
        new Collection(['103', 'Zuriel', 'Ávila', '42K', '00:23:40', '00:48:12']),
    ]);

    (new ParticipantsImport($import->id))->collection($rows);

    $participant = EventParticipant::where('bib_number', '103')->firstOrFail();
    $splits = $participant->result->splits;

    expect($splits)->toHaveCount(2)
        ->and($splits->firstWhere('label', '5K')->elapsed_time)->toBe('00:23:40')
        ->and($splits->firstWhere('label', '5K')->sequence)->toBe(1)
        ->and($splits->firstWhere('label', '10K')->elapsed_time)->toBe('00:48:12');
});

test('a split mapped to an empty cell for a given row is skipped, not created blank', function () {
    $mapping = [
        'bib_number' => 0, 'first_name' => 1, 'last_name' => 2, 'race_name' => 3,
        'splits' => [
            ['label' => '5K', 'column' => 4],
        ],
    ];
    $import = makeImport($this->edition, $mapping, $this->user);

    $rows = new Collection([
        new Collection(['104', 'María', 'Cruz', '42K', '']),
    ]);

    (new ParticipantsImport($import->id))->collection($rows);

    $participant = EventParticipant::where('bib_number', '104')->firstOrFail();
    expect($participant->result)->toBeNull();
});

test('re-importing the same row updates the existing split instead of duplicating it', function () {
    $mapping = [
        'bib_number' => 0, 'first_name' => 1, 'last_name' => 2, 'race_name' => 3,
        'splits' => [['label' => '5K', 'column' => 4]],
    ];
    $import = makeImport($this->edition, $mapping, $this->user);

    $row = new Collection(['105', 'Iker', 'Solís', '42K', '00:24:00']);
    (new ParticipantsImport($import->id))->collection(new Collection([$row]));

    $updatedRow = new Collection(['105', 'Iker', 'Solís', '42K', '00:23:15']);
    (new ParticipantsImport($import->id))->collection(new Collection([$updatedRow]));

    $participant = EventParticipant::where('bib_number', '105')->firstOrFail();
    expect($participant->result->splits)->toHaveCount(1)
        ->and($participant->result->splits->first()->elapsed_time)->toBe('00:23:15');
});
