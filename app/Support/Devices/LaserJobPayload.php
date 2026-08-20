<?php

namespace App\Support\Devices;

use App\Models\ProductionJob;

/**
 * What a device receives to know WHAT to engrave — never HOW (no power/
 * speed/frequency, see docs/adr/0002 §Invariantes de seguridad). Since
 * Slice 2 (docs/adr/0003 §Laser Job Payload), `front`/`back` are built
 * entirely from the job's frozen `ProductionArtifact` — never calls
 * PlateExportService or renders anything itself, so two requests for the
 * same job are always described by the exact same hashes.
 *
 * `front`/`back` are null for a job peeked via `GET jobs/next` — an
 * unclaimed job has no artifact yet, by design (artifacts are only frozen
 * once a job is claimed, see App\Services\Devices\ProductionJobClaimService).
 * Any job actually returned by `GET jobs/{job}` or a claim/transition
 * response always has one.
 */
final class LaserJobPayload
{
    /**
     * @param  array{width_mm: float|null, height_mm: float|null}  $dimensions
     * @param  array{download_url: string, sha256: string}|null  $front
     * @param  array{download_url: string, sha256: string, transform: string}|null  $back
     */
    private function __construct(
        public readonly int $jobId,
        public readonly int $plateId,
        public readonly string $serial,
        public readonly ?string $legacyCode,
        public readonly string $status,
        public readonly ?string $nextAction,
        public readonly array $dimensions,
        public readonly ?array $front,
        public readonly ?array $back,
    ) {}

    public static function fromJob(ProductionJob $job): self
    {
        $job->loadMissing(['plate.legacyCode', 'artifact']);
        $plate = $job->plate;
        $artifact = $job->artifact;

        return new self(
            jobId: $job->id,
            plateId: $plate->id,
            serial: $plate->serial_number,
            legacyCode: $plate->legacyCode?->code,
            status: $job->status->value,
            nextAction: $job->nextAction(),
            dimensions: [
                'width_mm' => $artifact?->width_mm !== null ? (float) $artifact->width_mm : null,
                'height_mm' => $artifact?->height_mm !== null ? (float) $artifact->height_mm : null,
            ],
            front: $artifact === null ? null : [
                'download_url' => route('api.v1.device.production.jobs.artifact', [$job, 'front']),
                'sha256' => $artifact->front_sha256,
            ],
            back: $artifact === null ? null : [
                'download_url' => route('api.v1.device.production.jobs.artifact', [$job, 'back']),
                'sha256' => $artifact->back_sha256,
                'transform' => $artifact->back_transform->value,
            ],
        );
    }
}
