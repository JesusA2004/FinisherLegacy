<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An Athlete's identity on an external system (a timing/registration
     * provider) — no real provider exists yet (Slice 4), so `provider` is
     * currently always `manual`/`csv`/`legacy_import` (see
     * docs/adr/0004-athlete-canonical-identity.md §External identities).
     *
     * `provider_connection_id` defaults to `''`, never `NULL`, specifically
     * so the 3-column unique index below behaves correctly — MySQL treats
     * every NULL in a unique index as distinct from every other NULL, so a
     * nullable `provider_connection_id` would silently let the same
     * provider+subject pair be inserted twice. An empty string is a normal
     * value for uniqueness purposes; NULL is not.
     */
    public function up(): void
    {
        Schema::create('athlete_external_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('athlete_id')->constrained('athletes')->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_connection_id')->default('');
            $table->string('external_subject_id');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_connection_id', 'external_subject_id'], 'athlete_external_identities_unique');
            $table->index('athlete_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athlete_external_identities');
    }
};
