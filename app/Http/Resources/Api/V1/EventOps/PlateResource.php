<?php

namespace App\Http\Resources\Api\V1\EventOps;

use App\Models\Plate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A generated Plate as any Event Ops API client needs it — serial +
 * Legacy Code + template version + the production job's current status/
 * next action, never internal ids/secrets beyond what Web already shows
 * on the same screen (docs/api/v1.md §Resources).
 *
 * @mixin Plate
 */
class PlateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $job = $this->latestProductionJob;

        return [
            'id' => $this->id,
            'serial_number' => $this->serial_number,
            'status' => $this->status->value,
            'legacy_code' => $this->legacyCode?->code,
            'template_version_id' => $this->plate_template_version_id,
            'production_job' => $job === null ? null : [
                'id' => $job->id,
                'status' => $job->status->value,
                'next_action' => $job->nextAction(),
            ],
        ];
    }
}
