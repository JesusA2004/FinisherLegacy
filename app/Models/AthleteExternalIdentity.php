<?php

namespace App\Models;

use Database\Factories\AthleteExternalIdentityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An Athlete's identity on an external system. `provider` is currently
 * always `manual`/`csv`/`legacy_import` — no real provider integration
 * exists yet (Slice 4). See docs/adr/0004-athlete-canonical-identity.md
 * §External identities for why `provider_connection_id` is `''`, not
 * `null`, when unused.
 */
#[Fillable(['athlete_id', 'provider', 'provider_connection_id', 'external_subject_id', 'metadata'])]
class AthleteExternalIdentity extends Model
{
    /** @use HasFactory<AthleteExternalIdentityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Athlete, $this> */
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }
}
