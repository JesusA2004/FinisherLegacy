<?php

namespace App\Support\Devices;

use Illuminate\Http\Request;

final class ConfirmDevicePairingData
{
    private function __construct(
        public readonly string $pollToken,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = $request->validate([
            'poll_token' => ['required', 'string'],
        ]);

        return new self(pollToken: $data['poll_token']);
    }
}
