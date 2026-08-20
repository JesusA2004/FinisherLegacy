<?php

namespace App\Imports;

use App\Actions\Integrations\IngestEventResult;
use App\Enums\ImportStatus;
use App\Enums\PreregistrationStatus;
use App\Models\EventEdition;
use App\Models\EventImport;
use App\Models\EventImportError;
use App\Models\EventParticipant;
use App\Models\EventPreregistration;
use App\Models\EventRace;
use App\Services\Integrations\EventIngestionService;
use App\Support\Integrations\ExternalParticipantData;
use App\Support\Integrations\ExternalResultData;
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

            [$ok, $error] = $this->importRow($row, $edition, $mapping, $racesByName);

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
     * @param  array<string, mixed>  $mapping
     * @param  Collection<string, EventRace>  $racesByName
     * @return array{0: bool, 1: ?array{code: string, message: string}}
     */
    private function importRow(Collection $row, EventEdition $edition, array $mapping, Collection $racesByName): array
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
            // Same canonical DTO + ingestion pipeline an API sync uses
            // (docs/adr/0005-unified-event-ingestion.md §53-55) — CSV is
            // just another source feeding App\Services\Integrations\EventIngestionService,
            // never a second upsert-then-match implementation. Identity
            // resolution/conflict handling inside it is unchanged from
            // Slice 3 (docs/adr/0004 §71-75): a conflict never fails the
            // row, `athlete_id` is just left null pending review.
            $data = ExternalParticipantData::fromArray([
                'external_participant_id' => null,
                'bib_number' => $bibNumber,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
            ]);

            $participant = app(EventIngestionService::class)->ingestParticipant(
                $data,
                $edition,
                $race,
                null,
                'import',
            );
        } catch (Throwable $e) {
            return [false, ['code' => 'db_error', 'message' => $e->getMessage()]];
        }

        $this->matchPreregistration($participant, $edition->id, $bibNumber, $email, $firstName, $lastName);
        $this->importResultAndSplits($participant, $row, $mapping, $field);

        return [true, null];
    }

    /**
     * Results/splits are entirely optional per §5 — a roster-only file with
     * none of these columns mapped must import exactly as it always has.
     * The split label comes straight from what the admin typed for that
     * column in the mapping step (e.g. "5K", "Swim"), since a generic
     * import can't otherwise guess whether a race is running or triathlon.
     *
     * @param  Collection<int, mixed>  $row
     * @param  array<string, mixed>  $mapping
     * @param  callable(string): ?string  $field
     */
    private function importResultAndSplits(EventParticipant $participant, Collection $row, array $mapping, callable $field): void
    {
        $officialTime = $field('official_time') ?: null;
        $pace = $field('pace') ?: null;

        $splitRows = [];
        foreach (array_values($mapping['splits'] ?? []) as $index => $splitDef) {
            $column = $splitDef['column'] ?? null;
            $label = trim((string) ($splitDef['label'] ?? ''));

            if ($column === null || $label === '') {
                continue;
            }

            $value = trim((string) ($row[$column] ?? ''));

            if ($value === '') {
                continue;
            }

            $splitRows[] = ['label' => $label, 'sequence' => $index + 1, 'elapsed_time' => $value];
        }

        if ($officialTime === null && $pace === null && $splitRows === []) {
            return;
        }

        // Same App\Actions\Integrations\IngestEventResult an API sync uses —
        // the participant is already known here, so this skips
        // EventIngestionService's participant-resolution step and calls the
        // ingest action directly (docs/adr/0005 §53-55, §61).
        app(IngestEventResult::class)->handle(
            $participant,
            ExternalResultData::fromArray([
                'external_participant_id' => (string) $participant->id,
                'official_time' => $officialTime,
                'pace' => $pace,
                'splits' => array_map(fn (array $s) => [
                    'type' => 'split',
                    'label' => $s['label'],
                    'sequence' => $s['sequence'],
                    'elapsed_time' => $s['elapsed_time'],
                ], $splitRows),
            ]),
            'csv',
        );
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
