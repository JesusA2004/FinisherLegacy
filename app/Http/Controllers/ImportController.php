<?php

namespace App\Http\Controllers;

use App\Models\EventEdition;
use App\Models\EventImport;
use App\Services\EventImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function __construct(private readonly EventImportService $imports) {}

    public function index(): Response
    {
        $imports = EventImport::with('eventEdition.event')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (EventImport $import) => [
                'id' => $import->id,
                'event' => $import->eventEdition?->event?->name,
                'edition' => $import->eventEdition?->name,
                'filename' => $import->original_filename,
                'status' => $import->status->value,
                'total_rows' => $import->total_rows,
                'successful_rows' => $import->successful_rows,
                'failed_rows' => $import->failed_rows,
                'created_at' => $import->created_at?->diffForHumans(),
            ]);

        return Inertia::render('imports/Index', ['imports' => $imports]);
    }

    public function create(): Response
    {
        return Inertia::render('imports/Create', [
            'editions' => EventEdition::with('event')
                ->orderByDesc('event_date')
                ->limit(100)
                ->get()
                ->map(fn (EventEdition $edition) => [
                    'id' => $edition->id,
                    'name' => $edition->event->name.' — '.$edition->name,
                ]),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $result = $this->imports->storeUpload($request->file('file'));

        return response()->json([
            'data' => [
                'temp_path' => $result['path'],
                'original_filename' => $request->file('file')->getClientOriginalName(),
                'headers' => $result['headers'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_edition_id' => ['required', 'integer', 'exists:event_editions,id'],
            'temp_path' => ['required', 'string'],
            'original_filename' => ['required', 'string'],
            'mapping' => ['required', 'array'],
            'mapping.bib_number' => ['required', 'integer'],
            'mapping.first_name' => ['required', 'integer'],
            'mapping.last_name' => ['required', 'integer'],
            'mapping.race_name' => ['required', 'integer'],
            'mapping.email' => ['nullable', 'integer'],
            'mapping.phone' => ['nullable', 'integer'],
        ]);

        abort_unless(Storage::disk('local')->exists($data['temp_path']), 422, 'El archivo expiró, vuelve a subirlo.');

        $edition = EventEdition::findOrFail((int) $data['event_edition_id']);

        $import = $this->imports->startImport(
            $edition,
            $data['temp_path'],
            $data['original_filename'],
            $data['mapping'],
            $request->user(),
        );

        return to_route('imports.show', $import);
    }

    public function show(EventImport $eventImport): Response
    {
        $eventImport->load(['eventEdition.event', 'errors' => fn ($q) => $q->orderBy('row_number')->limit(100)]);

        return Inertia::render('imports/Show', [
            'import' => [
                'id' => $eventImport->id,
                'event' => $eventImport->eventEdition?->event?->name,
                'edition' => $eventImport->eventEdition?->name,
                'filename' => $eventImport->original_filename,
                'status' => $eventImport->status->value,
                'total_rows' => $eventImport->total_rows,
                'processed_rows' => $eventImport->processed_rows,
                'successful_rows' => $eventImport->successful_rows,
                'failed_rows' => $eventImport->failed_rows,
            ],
            'errors' => $eventImport->errors->map(fn ($error) => [
                'row_number' => $error->row_number,
                'error_message' => $error->error_message,
            ]),
        ]);
    }
}
