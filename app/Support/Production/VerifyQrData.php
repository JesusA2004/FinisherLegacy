<?php

namespace App\Support\Production;

use Illuminate\Http\Request;

final class VerifyQrData
{
    private function __construct(
        public readonly string $decodedValue,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = $request->validate([
            'decoded_value' => ['required', 'string', 'max:500'],
        ]);

        return new self(decodedValue: $data['decoded_value']);
    }

    /**
     * For callers with no HTTP Request to validate — e.g.
     * `finisher:simulate-event-day`, which calls
     * App\Actions\Production\VerifyProductionQr directly instead of over
     * HTTP.
     */
    public static function make(string $decodedValue): self
    {
        return new self(decodedValue: $decodedValue);
    }
}
