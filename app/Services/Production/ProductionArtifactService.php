<?php

namespace App\Services\Production;

use App\Models\ProductionArtifact;
use App\Models\ProductionJob;
use App\Services\PlateExportService;
use App\Support\PlateRendererVersion;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * The only writer of ProductionArtifact rows/files. Renders once, from the
 * plate's frozen snapshot + its exact (published, immutable) template
 * version, and never again for that job — see docs/adr/0003
 * §Artifact/Same-input-same-output. Reuses the exact same
 * App\Services\PlateExportService the manual "Descargar para láser"
 * fallback already calls, so an automatic artifact and a manual download of
 * the same plate/version are always byte-identical (docs/adr/0003 §61-63).
 */
class ProductionArtifactService
{
    public function __construct(private readonly PlateExportService $exporter) {}

    /**
     * Idempotent — a job already holding an artifact just returns it,
     * never regenerates (the whole point: same input, same output, exactly
     * once).
     */
    public function ensureGenerated(ProductionJob $job): ProductionArtifact
    {
        $existing = $job->artifact()->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->generate($job);
    }

    private function generate(ProductionJob $job): ProductionArtifact
    {
        $job->loadMissing(['plate.plateTemplate', 'plate.plateTemplateVersion']);
        $plate = $job->plate;
        $version = $plate->plateTemplateVersion;

        abort_if($version === null, 422, 'Esta placa no tiene un molde asignado — no se puede generar el archivo de producción.');

        $template = $plate->plateTemplate;

        // Both faces render fully in memory before anything touches disk —
        // a render-time failure (e.g. a malformed template) never leaves a
        // half-written artifact behind. Text-as-paths so the artifact never
        // depends on a font being installed wherever it's opened (see
        // docs/plate-production.md §9 — the font-outline fix this reuses).
        $front = $this->exporter->exportFace($plate, 'front', 'svg', textAsPaths: true);
        $back = $this->exporter->exportFace($plate, 'back', 'svg', textAsPaths: true);

        $disk = Storage::disk((string) config('finisher.production_artifact_disk', 'local'));
        $dir = "production/artifacts/{$job->id}";
        $frontPath = "{$dir}/front.svg";
        $backPath = "{$dir}/back.svg";

        try {
            if (! $disk->put($frontPath, $front['content']) || ! $disk->put($backPath, $back['content'])) {
                throw new RuntimeException('No se pudo escribir el archivo de producción en el almacenamiento.');
            }

            return ProductionArtifact::create([
                'production_job_id' => $job->id,
                'plate_id' => $plate->id,
                'plate_template_version_id' => $version->id,
                'renderer_version' => PlateRendererVersion::CURRENT,
                'format' => 'svg',
                'front_storage_path' => $frontPath,
                'front_sha256' => hash('sha256', $front['content']),
                'back_storage_path' => $backPath,
                'back_sha256' => hash('sha256', $back['content']),
                'width_mm' => $template?->width_mm,
                'height_mm' => $template?->height_mm,
                'back_transform' => $template?->back_transform->value ?? 'none',
                'generated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $disk->deleteDirectory($dir);

            throw $e;
        }
    }
}
