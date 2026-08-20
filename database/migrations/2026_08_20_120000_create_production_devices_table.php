<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A ProductionDevice is a paired Finisher Event Desktop instance (or
     * simulator) — identity only. No credentials live here: its Sanctum
     * token is stored the normal Sanctum way, in `personal_access_tokens`
     * (morphed to this model), never as a column on this table. See
     * docs/adr/0002-device-production-api.md.
     */
    public function up(): void
    {
        Schema::create('production_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('station_code')->nullable();
            $table->foreignId('machine_profile_id')->nullable()->constrained('machine_profiles')->nullOnDelete();
            $table->foreignId('event_edition_id')->nullable()->constrained('event_editions')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('last_seen_at')->nullable();
            $table->string('app_version')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_devices');
    }
};
