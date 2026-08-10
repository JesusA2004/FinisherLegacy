<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use App\Imports\ParticipantsImport;
use App\Models\EventEdition;
use App\Models\EventImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * CSV/XLSX participant imports run through here — reads just the header
 * row for the mapping step and the row count for progress tracking, then
 * hands the file off to the queued ParticipantsImport job, which does the
 * real row-by-row work.
 */
class EventImportService
{
    /**
     * @return array{path: string, headers: list<array{index: int, label: string}>}
     */
    public function storeUpload(UploadedFile $file): array
    {
        $path = $file->store('imports/tmp', 'local');
        abort_if($path === false, 500, 'No se pudo guardar el archivo.');
        $fullPath = Storage::disk('local')->path($path);

        $reader = IOFactory::createReaderForFile($fullPath);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($fullPath)->getActiveSheet();
        $firstRow = $sheet->rangeToArray('A1:'.$sheet->getHighestColumn().'1')[0] ?? [];

        $headers = [];
        foreach (array_values($firstRow) as $index => $value) {
            $label = trim((string) $value);
            $headers[] = ['index' => $index, 'label' => $label !== '' ? $label : 'Columna '.($index + 1)];
        }

        return ['path' => $path, 'headers' => $headers];
    }

    /**
     * @param  array<string, int|null>  $mapping
     */
    public function startImport(EventEdition $edition, string $tempPath, string $originalFilename, array $mapping, User $user): EventImport
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION) ?: 'csv';
        $finalPath = "imports/{$edition->id}/".uniqid('import_', true).'.'.$extension;
        Storage::disk('local')->move($tempPath, $finalPath);

        $import = EventImport::create([
            'event_edition_id' => $edition->id,
            'type' => ImportType::Participants,
            'file_path' => $finalPath,
            'original_filename' => $originalFilename,
            'column_mapping' => $mapping,
            'status' => ImportStatus::Pending,
            'total_rows' => $this->countDataRows(Storage::disk('local')->path($finalPath)),
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'created_by' => $user->id,
        ]);

        // ParticipantsImport implements ShouldQueue, so this queues the job
        // rather than running it inline.
        Excel::import(new ParticipantsImport($import->id), $finalPath, 'local');

        return $import;
    }

    private function countDataRows(string $fullPath): int
    {
        $reader = IOFactory::createReaderForFile($fullPath);
        $reader->setReadDataOnly(true);
        $highestRow = $reader->load($fullPath)->getActiveSheet()->getHighestDataRow();

        return max(0, $highestRow - 1);
    }
}
