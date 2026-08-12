<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PlateTemplateVersionStatus;
use App\Http\Controllers\Controller;
use App\Models\EventEdition;
use App\Models\EventPlateTemplate;
use App\Models\EventProductionCheck;
use App\Models\PlateTemplateVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Preparar evento para producción": assign the default plate template version an
 * edition (and optionally each race) uses, and record the one checklist item that's
 * a human attestation rather than something derivable from data — the physical QR
 * scan test.
 */
class ProductionSetupController extends Controller
{
    public function show(EventEdition $eventEdition): Response
    {
        $eventEdition->load(['event', 'races', 'plateTemplateAssignments.plateTemplateVersion.plateTemplate', 'productionCheck']);

        $publishedVersions = PlateTemplateVersion::query()
            ->with('plateTemplate')
            ->where('status', PlateTemplateVersionStatus::Published)
            ->whereHas('plateTemplate', fn ($q) => $q->where('active', true))
            ->get()
            ->map(fn (PlateTemplateVersion $version) => [
                'id' => $version->id,
                'label' => "{$version->plateTemplate->name} — V{$version->version}",
            ]);

        $defaultAssignment = $eventEdition->plateTemplateAssignments->firstWhere('event_race_id', null);

        return Inertia::render('admin/events/ProductionSetup', [
            'edition' => [
                'id' => $eventEdition->id,
                'name' => $eventEdition->event->name.' — '.$eventEdition->name,
                'status' => $eventEdition->status->value,
            ],
            'races' => $eventEdition->races->map(fn ($race) => [
                'id' => $race->id,
                'name' => $race->name,
                'assignment' => $eventEdition->plateTemplateAssignments->firstWhere('event_race_id', $race->id)?->plate_template_version_id,
            ]),
            'availableVersions' => $publishedVersions,
            'defaultAssignment' => $defaultAssignment ? [
                'plate_template_version_id' => $defaultAssignment->plate_template_version_id,
                'template_name' => $defaultAssignment->plateTemplateVersion->plateTemplate->name,
                'version' => $defaultAssignment->plateTemplateVersion->version,
            ] : null,
            'checklist' => [
                'template_assigned' => $defaultAssignment !== null,
                'version_published' => $defaultAssignment?->plateTemplateVersion->status === PlateTemplateVersionStatus::Published,
                'qr_tested_at' => $eventEdition->productionCheck?->qr_tested_at?->toIso8601String(),
                'qr_tested_by' => $eventEdition->productionCheck?->qrTestedBy?->name,
            ],
        ]);
    }

    public function assignTemplate(Request $request, EventEdition $eventEdition): RedirectResponse
    {
        $data = $request->validate([
            'plate_template_version_id' => ['required', 'integer', 'exists:plate_template_versions,id'],
            'event_race_id' => ['nullable', 'integer', 'exists:event_races,id'],
        ]);

        EventPlateTemplate::query()
            ->where('event_edition_id', $eventEdition->id)
            ->where('event_race_id', $data['event_race_id'] ?? null)
            ->update(['active' => false]);

        EventPlateTemplate::create([
            'event_edition_id' => $eventEdition->id,
            'event_race_id' => $data['event_race_id'] ?? null,
            'plate_template_version_id' => $data['plate_template_version_id'],
            'is_default' => true,
            'active' => true,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Molde asignado al evento.']);

        return back();
    }

    public function markQrTested(Request $request, EventEdition $eventEdition): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:500']]);

        EventProductionCheck::updateOrCreate(
            ['event_edition_id' => $eventEdition->id],
            ['qr_tested_at' => now(), 'qr_tested_by' => $request->user()->id, 'notes' => $data['notes'] ?? null],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Prueba física de QR registrada.']);

        return back();
    }
}
