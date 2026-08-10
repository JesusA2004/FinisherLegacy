<?php

use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Enums\PreregistrationStatus;
use App\Models\EventEdition;
use App\Models\EventImport;
use App\Models\EventImportError;
use App\Models\EventParticipant;
use App\Models\EventPreregistration;
use App\Models\EventRace;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RolePermissionSeeder::class);
    $this->manager = User::factory()->create();
    $this->manager->assignRole('event_manager');
});

function csvFixture(): UploadedFile
{
    $csv = implode("\n", [
        'Bib,Nombre,Apellido,Distancia,Correo',
        '101,Ada,Lovelace,10K,ada@example.com',
        '102,Alan,Turing,10K,alan@example.com',
        '103,,SinNombre,10K,x@example.com', // invalid: missing first name
    ]);

    return UploadedFile::fake()->createWithContent('participants.csv', $csv);
}

function participantMapping(): array
{
    return [
        'bib_number' => 0,
        'first_name' => 1,
        'last_name' => 2,
        'race_name' => 3,
        'email' => 4,
    ];
}

test('import routes are blocked without the imports.manage permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('imports.index'))->assertForbidden();
});

test('uploading a file returns detected column headers', function () {
    $response = $this->actingAs($this->manager)->postJson(route('imports.upload'), [
        'file' => csvFixture(),
    ]);

    $response->assertOk();
    expect($response->json('data.headers'))->toHaveCount(5);
    expect($response->json('data.headers.0.label'))->toBe('Bib');
});

test('a full participant import creates participants, records errors, and matches preregistrations', function () {
    $edition = EventEdition::factory()->create(['status' => EditionStatus::Published]);
    $edition->event()->update(['status' => EventStatus::Published]);
    EventRace::factory()->create(['event_edition_id' => $edition->id, 'name' => '10K']);

    $preregistration = EventPreregistration::factory()->create([
        'event_edition_id' => $edition->id,
        'bib_number' => '101',
        'status' => PreregistrationStatus::Pending,
    ]);

    $uploadResponse = $this->actingAs($this->manager)->postJson(route('imports.upload'), [
        'file' => csvFixture(),
    ]);

    $tempPath = $uploadResponse->json('data.temp_path');
    $originalFilename = $uploadResponse->json('data.original_filename');

    $response = $this->actingAs($this->manager)->post(route('imports.store'), [
        'event_edition_id' => $edition->id,
        'temp_path' => $tempPath,
        'original_filename' => $originalFilename,
        'mapping' => participantMapping(),
    ]);

    $response->assertRedirect();

    $import = EventImport::firstOrFail();
    expect($import->status->value)->toBe('completed_with_errors')
        ->and($import->total_rows)->toBe(3)
        ->and($import->successful_rows)->toBe(2)
        ->and($import->failed_rows)->toBe(1);

    expect(EventParticipant::where('event_edition_id', $edition->id)->count())->toBe(2);
    $ada = EventParticipant::where('bib_number', '101')->firstOrFail();
    expect($ada->full_name)->toBe('Ada Lovelace')
        ->and($ada->email)->toBe('ada@example.com');

    expect(EventImportError::where('event_import_id', $import->id)->count())->toBe(1);

    expect($preregistration->fresh()->status)->toBe(PreregistrationStatus::Matched);
    expect($preregistration->fresh()->matched_participant_id)->toBe($ada->id);
});

test('re-importing the same bib number updates the existing participant instead of duplicating it', function () {
    $edition = EventEdition::factory()->create(['status' => EditionStatus::Published]);
    $edition->event()->update(['status' => EventStatus::Published]);
    EventRace::factory()->create(['event_edition_id' => $edition->id, 'name' => '10K']);

    foreach ([1, 2] as $_) {
        $upload = $this->actingAs($this->manager)->postJson(route('imports.upload'), ['file' => csvFixture()]);

        $this->actingAs($this->manager)->post(route('imports.store'), [
            'event_edition_id' => $edition->id,
            'temp_path' => $upload->json('data.temp_path'),
            'original_filename' => $upload->json('data.original_filename'),
            'mapping' => participantMapping(),
        ]);
    }

    expect(EventParticipant::where('event_edition_id', $edition->id)->where('bib_number', '101')->count())->toBe(1);
});
