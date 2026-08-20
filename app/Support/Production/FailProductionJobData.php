<?php

namespace App\Support\Production;

use App\Enums\ProductionFailureCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class FailProductionJobData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function __construct(
        public readonly ProductionFailureCode $errorCode,
        public readonly ?string $message,
        public readonly ?array $metadata,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = $request->validate([
            'error_code' => ['required', Rule::enum(ProductionFailureCode::class)],
            'message' => ['nullable', 'string', 'max:500'],
            'metadata' => ['nullable', 'array'],
        ]);

        return new self(
            errorCode: ProductionFailureCode::from($data['error_code']),
            message: $data['message'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
