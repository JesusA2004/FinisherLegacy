<?php

namespace App\Imports;

use App\Enums\ImportStatus;
use App\Enums\ParticipantSource;
use App\Enums\PreregistrationStatus;
use App\Enums\RegistrationStatus;
use App\Enums\VerificationStatus;
use App\Models\EventImport;
use App\Models\EventImportError;
use App\Models\EventParticipant;
use App\Models\EventPreregistration;
use App\Models\EventRace;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Throwable;

/**
 * Row-by-row participant import. Column mapping is user-chosen (not fixed
 * header names) — see EventImportService::readHeaders() — so this reads by
 * column index per EventImport::column_mapping and handles each row's
 * errors individually instead of letting one bad row abort the batch.
 *
 * WithStartRow(2) skips the header row at the reader level; WithChunkReading
 * is required by maatwebsite/excel for any ShouldQueue import and also
 * keeps memory flat for larger rosters. total_rows is precomputed by
 * EventImportService::startImport() before this job ever runs, since a
 * chunked reader never sees the whole file at once.
 */
class ParticipantsImport implements ShouldQueue, ToCollection, WithChunkReading, WithStartRow
{
    public function __construct(private readonly int $eventImportId) {}

    public function chunkSize(): int
    {
        return 200;
    }

    public function startRow(): int
    {
        return 2;
    }

    /**
     * @param  Collection<int, Collection<int, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        $import = EventImport::with('eventEdition')->findOrFail($this->eventImportId);

        if ($import->status === ImportStatus::Pending) {
            $import->update(['status' => ImportStatus::Processing, 'started_at' => now()]);
        }

        $mapping = $import->column_mapping ?? [];
        $edition = $import->eventEdition;

        $racesByName = EventRace::query()
            ->where('event_edition_id', $edition->id)
            ->get()
            ->keyBy(fn (EventRace $race) => mb_strtolower(trim($race->name)));

        foreach ($rows as $offset => $row) {
            $rowNumber = $this->startRow() + $offset;

            [$ok, $error] = $this->importRow($row, $edition->id, $mapping, $racesByName);

            if ($ok) {
                $import->increment('successful_rows');
            } else {
                $import->increment('failed_rows');
                EventImportError::create([
                    'event_import_id' => $import->id,
                    'row_number' => $rowNumber,
                    'raw_data' => $row->toArray(),
                    'error_code' => $error['code'],
                    'error_message' => $error['message'],
                ]);
            }

            $import->increment('processed_rows');
        }

        $import->refresh();

        if ($import->processed_rows >= (int) $import->total_rows) {
            $import->update([
                'status' => match (true) {
                    $import->failed_rows === 0 => ImportStatus::Completed,
                    $import->successful_rows === 0 => ImportStatus::Failed,
                    default => ImportStatus::CompletedWithErrors,
                },
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * @param  Collection<int, mixed>  $row
     * @param  array<string, int|null>  $mapping
     * @param  Collection<string, EventRace>  $racesByName
     * @return array{0: bool, 1: ?array{code: string, message: string}}
     */
    private function importRow(Collection $row, int $editionId, array $mapping, Collection $racesByName): array
    {
        $field = fn (string $key): ?string => isset($mapping[$key])
            ? trim((string) ($row[$mapping[$key]] ?? ''))
            : null;

        $bibNumber = $field('bib_number');
        $firstName = $field('first_name');
        $lastName = $field('last_name');
        $raceName = $field('race_name');
        $email = $field('email') ?: null;
        $phone = $field('phone') ?: null;

        if (! $bibNumber) {
            return [false, ['code' => 'missing_bib', 'message' => 'Falta el número de corredor.']];
        }

        if (! $firstName || ! $lastName) {
            return [false, ['code' => 'missing_name', 'message' => 'Falta el nombre o apellido.']];
        }

        $race = $raceName ? $racesByName->get(mb_strtolower($raceName)) : null;

        if (! $race) {
            return [false, ['code' => 'unknown_race', 'message' => "No se encontró la distancia \"{$raceName}\" en este evento."]];
        }

        try {
            $participant = EventParticipant::updateOrCreate(
                ['event_edition_id' => $editionId, 'bib_number' => $bibNumber],
                [
                    'event_race_id' => $race->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'full_name' => trim("{$firstName} {$lastName}"),
                    'email' => $email,
                    'phone' => $phone,
                    'registration_status' => RegistrationStatus::Registered,
                    'source' => ParticipantSource::Csv,
                    'verification_status' => VerificationStatus::Unverified,
                ],
            );
        } catch (Throwable $e) {
            return [false, ['code' => 'db_error', 'message' => $e->getMessage()]];
        }

        $this->matchPreregistration($participant, $editionId, $bibNumber, $email, $firstName, $lastName);

        return [true, null];
    }

    private function matchPreregistration(EventParticipant $participant, int $editionId, string $bibNumber, ?string $email, string $firstName, string $lastName): void
    {
        $candidates = EventPreregistration::query()
            ->where('event_edition_id', $editionId)
            ->where('status', PreregistrationStatus::Pending)
            ->where(function ($query) use ($bibNumber, $email, $firstName, $lastName) {
                $query->where('bib_number', $bibNumber);

                if ($email) {
                    $query->orWhere(function ($q) use ($email, $firstName, $lastName) {
                        $q->where('email', $email)
                            ->where('first_name', $firstName)
                            ->where('last_name', $lastName);
                    });
                }
            })
            ->get();

        // Ambiguous matches are left for manual pairing rather than guessed.
        if ($candidates->count() !== 1) {
            return;
        }

        $candidates->first()->update([
            'matched_participant_id' => $participant->id,
            'status' => PreregistrationStatus::Matched,
        ]);
    }
}
