<?php

namespace App\Http\Resources\Api\V1\Devices;

use App\Support\Devices\LaserJobPayload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LaserJobPayload
 */
class ProductionJobDeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LaserJobPayload $payload */
        $payload = $this->resource;

        return [
            'job_id' => $payload->jobId,
            'status' => $payload->status,
            'next_action' => $payload->nextAction,
            'plate' => [
                'id' => $payload->plateId,
                'serial' => $payload->serial,
                'legacy_code' => $payload->legacyCode,
            ],
            'dimensions' => $payload->dimensions,
            'front' => $payload->front,
            'back' => $payload->back,
        ];
    }
}
