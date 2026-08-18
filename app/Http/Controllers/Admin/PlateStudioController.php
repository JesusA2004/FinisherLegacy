<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PlateTemplateVersionStatus;
use App\Http\Controllers\Controller;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
use App\Services\PlateTemplateRenderService;
use App\Support\PlateDynamicFields;
use App\Support\PlateRenderData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Plate Studio: define molds (PlateTemplate + versioned front/back configuration)
 * and preview them. Actually generating a physical Plate from a published version
 * happens in OperatorController — this controller only owns the design side.
 */
class PlateStudioController extends Controller
{
    public function __construct(private readonly PlateTemplateRenderService $renderer) {}

    public function index(): Response
    {
        $templates = PlateTemplate::query()
            ->withCount('plates')
            ->with(['versions' => fn ($q) => $q->orderByDesc('version')])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PlateTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'width_mm' => (float) $template->width_mm,
                'height_mm' => (float) $template->height_mm,
                'active' => $template->active,
                'plates_count' => $template->plates_count,
                'versions' => $template->versions->map(fn (PlateTemplateVersion $v) => [
                    'id' => $v->id,
                    'version' => $v->version,
                    'status' => $v->status->value,
                ]),
            ]);

        return Inertia::render('admin/plate-studio/Home', ['templates' => $templates]);
    }

    public function edit(PlateTemplate $plateTemplate, PlateTemplateVersion $plateTemplateVersion): Response
    {
        abort_unless($plateTemplateVersion->plate_template_id === $plateTemplate->id, 404);

        return Inertia::render('admin/plate-studio/Editor', [
            'template' => [
                'id' => $plateTemplate->id,
                'name' => $plateTemplate->name,
                'slug' => $plateTemplate->slug,
                'width_mm' => (float) $plateTemplate->width_mm,
                'height_mm' => (float) $plateTemplate->height_mm,
                'safe_margin_mm' => $plateTemplate->safe_margin_mm !== null ? (float) $plateTemplate->safe_margin_mm : 0,
                'orientation' => $plateTemplate->orientation,
                'material' => $plateTemplate->material,
            ],
            'version' => [
                'id' => $plateTemplateVersion->id,
                'version' => $plateTemplateVersion->version,
                'status' => $plateTemplateVersion->status->value,
                'editable' => $plateTemplateVersion->isEditable(),
                'front_configuration' => $plateTemplateVersion->front_configuration,
                'back_configuration' => $plateTemplateVersion->back_configuration,
            ],
            'fieldsCatalog' => PlateDynamicFields::catalog(),
        ]);
    }

    /**
     * Live preview while editing — renders from whatever draft config the browser
     * currently holds (not necessarily saved yet), always with demo athlete data, so
     * the same PlateTemplateRenderService drives both this and the final export.
     */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'width_mm' => ['required', 'numeric', 'min:1'],
            'height_mm' => ['required', 'numeric', 'min:1'],
            'safe_margin_mm' => ['nullable', 'numeric', 'min:0'],
            'face' => ['required', Rule::in(['front', 'back'])],
            'mode' => ['required', Rule::in(['product', 'production'])],
            'elements' => ['present', 'array'],
        ]);

        $template = (new PlateTemplate([
            'width_mm' => $data['width_mm'],
            'height_mm' => $data['height_mm'],
            'safe_margin_mm' => $data['safe_margin_mm'] ?? 0,
        ]));

        $version = new PlateTemplateVersion([
            'front_configuration' => $data['face'] === 'front' ? ['elements' => $data['elements']] : ['elements' => []],
            'back_configuration' => $data['face'] === 'back' ? ['elements' => $data['elements']] : ['elements' => []],
        ]);
        $version->setRelation('plateTemplate', $template);

        $renderData = PlateRenderData::demo();
        $svg = $this->renderer->renderSvg($version, $data['face'], $renderData, $data['mode']);
        $warnings = $this->renderer->warnings($version, $data['face'], $renderData)['warnings'];

        return response()->json(['svg' => $svg, 'warnings' => $warnings]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'width_mm' => ['required', 'numeric', 'min:1', 'max:500'],
            'height_mm' => ['required', 'numeric', 'min:1', 'max:500'],
            'material' => ['nullable', 'string', 'max:100'],
            'orientation' => ['required', Rule::in(['landscape', 'portrait'])],
            'safe_margin_mm' => ['nullable', 'numeric', 'min:0'],
        ]);

        $plateTemplateVersion = DB::transaction(function () use ($data, $request) {
            $template = PlateTemplate::create([
                ...$data,
                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
                'created_by' => $request->user()->id,
                'active' => true,
            ]);

            return PlateTemplateVersion::create([
                'plate_template_id' => $template->id,
                'version' => 1,
                'front_configuration' => ['elements' => []],
                'back_configuration' => ['elements' => []],
                'status' => PlateTemplateVersionStatus::Draft,
                'created_by' => $request->user()->id,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Molde creado. Ahora diséñalo.']);

        return to_route('admin.plate-studio.edit', [$plateTemplateVersion->plate_template_id, $plateTemplateVersion->id]);
    }

    public function update(Request $request, PlateTemplate $plateTemplate): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'material' => ['nullable', 'string', 'max:100'],
            'safe_margin_mm' => ['nullable', 'numeric', 'min:0'],
            'active' => ['required', 'boolean'],
        ]);

        $plateTemplate->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Molde actualizado.']);

        return back();
    }

    public function duplicate(Request $request, PlateTemplate $plateTemplate): RedirectResponse
    {
        $source = $plateTemplate->versions()->latest('version')->firstOrFail();

        $newVersion = DB::transaction(function () use ($plateTemplate, $source, $request) {
            $clone = PlateTemplate::create([
                'name' => "{$plateTemplate->name} (copia)",
                'slug' => Str::slug($plateTemplate->name).'-'.Str::lower(Str::random(5)),
                'description' => $plateTemplate->description,
                'width_mm' => $plateTemplate->width_mm,
                'height_mm' => $plateTemplate->height_mm,
                'material' => $plateTemplate->material,
                'orientation' => $plateTemplate->orientation,
                'safe_margin_mm' => $plateTemplate->safe_margin_mm,
                'created_by' => $request->user()->id,
                'active' => true,
            ]);

            return PlateTemplateVersion::create([
                'plate_template_id' => $clone->id,
                'version' => 1,
                'front_configuration' => $source->front_configuration,
                'back_configuration' => $source->back_configuration,
                'status' => PlateTemplateVersionStatus::Draft,
                'created_by' => $request->user()->id,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Molde duplicado.']);

        return to_route('admin.plate-studio.edit', [$newVersion->plate_template_id, $newVersion->id]);
    }

    public function archiveTemplate(PlateTemplate $plateTemplate): RedirectResponse
    {
        // Never deleted — archiving only hides it from "assign to event" pickers.
        // Historical plates keep their plate_template_version_id untouched.
        $plateTemplate->update(['active' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Molde archivado. Las placas ya generadas no se ven afectadas.']);

        return back();
    }

    public function createVersion(Request $request, PlateTemplate $plateTemplate): RedirectResponse
    {
        $latest = $plateTemplate->versions()->latest('version')->firstOrFail();

        $newVersion = PlateTemplateVersion::create([
            'plate_template_id' => $plateTemplate->id,
            'version' => $plateTemplate->nextVersionNumber(),
            'front_configuration' => $latest->front_configuration,
            'back_configuration' => $latest->back_configuration,
            'status' => PlateTemplateVersionStatus::Draft,
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Versión {$newVersion->version} creada a partir de la anterior."]);

        return to_route('admin.plate-studio.edit', [$plateTemplate->id, $newVersion->id]);
    }

    public function updateVersion(Request $request, PlateTemplateVersion $plateTemplateVersion): RedirectResponse
    {
        abort_unless($plateTemplateVersion->isEditable(), 422, 'Solo se pueden editar versiones en borrador. Crea una nueva versión para modificar un molde publicado.');

        $data = $request->validate([
            'front_configuration' => ['required', 'array'],
            'front_configuration.elements' => ['present', 'array'],
            'back_configuration' => ['required', 'array'],
            'back_configuration.elements' => ['present', 'array'],
        ]);

        $plateTemplateVersion->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Molde guardado.']);

        return back();
    }

    public function publish(PlateTemplateVersion $plateTemplateVersion): RedirectResponse
    {
        abort_unless($plateTemplateVersion->isEditable(), 422, 'Esta versión ya no está en borrador.');

        $plateTemplateVersion->loadMissing('plateTemplate');
        $minQr = $plateTemplateVersion->plateTemplate->minimum_validated_qr_size_mm;

        if ($minQr !== null) {
            /** @var list<array<string, mixed>> $backElements */
            $backElements = $plateTemplateVersion->back_configuration['elements'] ?? [];
            $tooSmall = collect($backElements)
                ->where('type', 'qr')
                ->first(fn (array $el) => (float) ($el['width_mm'] ?? 0) < (float) $minQr);

            abort_if($tooSmall !== null, 422, sprintf(
                'El QR del reverso mide %.1fmm — por debajo de los %.1fmm validados físicamente para este molde. Ajusta el tamaño antes de publicar.',
                (float) ($tooSmall['width_mm'] ?? 0),
                (float) $minQr,
            ));
        }

        $plateTemplateVersion->update(['status' => PlateTemplateVersionStatus::Published]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Versión {$plateTemplateVersion->version} publicada. Ya puede asignarse a un evento."]);

        return back();
    }

    public function archiveVersion(PlateTemplateVersion $plateTemplateVersion): RedirectResponse
    {
        $plateTemplateVersion->update(['status' => PlateTemplateVersionStatus::Archived]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Versión archivada. Las placas ya generadas con ella no se ven afectadas.']);

        return back();
    }

    /**
     * Calibration print: fixed demo content clearly marked as a test, never tied to a
     * real Plate/LegacyCode — used to adjust the laser machine before an event.
     */
    public function testExport(PlateTemplateVersion $plateTemplateVersion, string $face): HttpResponse
    {
        abort_unless(in_array($face, ['front', 'back'], true), 404);

        $svg = $this->renderer->renderSvg($plateTemplateVersion, $face, PlateRenderData::demo(), PlateTemplateRenderService::MODE_PRODUCTION);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => "attachment; filename=\"test-grabado-{$plateTemplateVersion->id}-{$face}.svg\"",
        ]);
    }
}
