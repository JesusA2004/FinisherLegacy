<?php

namespace App\Support\Devices;

use App\Models\ProductionJob;
use App\Services\PlateExportService;

/**
 * What a device receives to know WHAT to engrave — never HOW (no power/
 * speed/frequency, see docs/adr/0002 §Invariantes de seguridad). Built from
 * the same PlateExportService the manual-download fallback already uses
 * (App\Services\PlateExportService), so the device and a human downloading
 * the same plate always see byte-identical artifacts — never a second
 * render path that could disagree with the first (§18-21 of the Slice 1
 * spec). SHA-256 is computed from that same render, not stored separately,
 * so it can never drift from the content it describes.
 */
final class LaserJobPayload
{
    /**
     * @param  array{width_mm: float|null, height_mm: float|null}  $dimensions
     * @param  array{download_url: string, sha256: string}  $front
     * @param  array{download_url: string, sha256: string, transform: string}  $back
     */
    private function __construct(
        public readonly int $jobId,
        public readonly int $plateId,
        public readonly string $serial,
        public readonly ?string $legacyCode,
        public readonly array $dimensions,
        public readonly array $front,
        public readonly array $back,
    ) {}

    public static function fromJob(ProductionJob $job, PlateExportService $exporter): self
    {
        $job->loadMissing(['plate.plateTemplate', 'plate.legacyCode']);
        $plate = $job->plate;
        $template = $plate->plateTemplate;

        $frontContent = $exporter->exportFace($plate, 'front', 'svg')['content'];
        $backContent = $exporter->exportFace($plate, 'back', 'svg')['content'];

        return new self(
            jobId: $job->id,
            plateId: $plate->id,
            serial: $plate->serial_number,
            legacyCode: $plate->legacyCode?->code,
            dimensions: [
                'width_mm' => $template !== null ? (float) $template->width_mm : null,
                'height_mm' => $template !== null ? (float) $template->height_mm : null,
            ],
            front: [
                'download_url' => route('api.v1.device.production.jobs.artifact', [$job, 'front']),
                'sha256' => hash('sha256', $frontContent),
            ],
            back: [
                'download_url' => route('api.v1.device.production.jobs.artifact', [$job, 'back']),
                'sha256' => hash('sha256', $backContent),
                'transform' => $template?->back_transform->value ?? 'none',
            ],
        );
    }
}
