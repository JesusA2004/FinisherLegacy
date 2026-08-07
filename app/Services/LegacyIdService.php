<?php

namespace App\Services;

use App\Enums\LegacyIdStatus;
use App\Models\LegacyId;
use App\Models\User;
use App\Support\CodeGenerator;

class LegacyIdService
{
    /**
     * Issue a Legacy ID for a user, or return the one they already have.
     */
    public function issueFor(User $user): LegacyId
    {
        $existing = $user->legacyId()->first();

        if ($existing) {
            return $existing;
        }

        $code = CodeGenerator::unique('FL', fn (string $code) => LegacyId::query()->where('code', $code)->exists());

        return LegacyId::create([
            'user_id' => $user->id,
            'code' => $code,
            'status' => LegacyIdStatus::Active,
            'issued_at' => now(),
        ]);
    }
}
