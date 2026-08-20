<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `code` is a human-readable correlator only (shown on the desktop
     * screen and in the admin "Estaciones" pending list, so a Super Admin
     * can tell two simultaneous pairing attempts apart) — it is never
     * accepted by the API as a credential. `poll_token_hash` is the real
     * secret: only the requesting desktop holds the plaintext poll token,
     * and only its SHA-256 hash is stored here, the same way Sanctum never
     * persists a plaintext personal access token either.
     */
    public function up(): void
    {
        Schema::create('device_pairing_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->string('poll_token_hash', 64)->unique();
            $table->string('status')->default('pending');

            $table->string('requested_name');
            $table->string('requested_station_code')->nullable();
            $table->string('requested_app_version')->nullable();
            $table->json('requested_capabilities')->nullable();

            $table->foreignId('production_device_id')->nullable()->constrained('production_devices')->nullOnDelete();
            $table->foreignId('machine_profile_id')->nullable()->constrained('machine_profiles')->nullOnDelete();
            $table->foreignId('event_edition_id')->nullable()->constrained('event_editions')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at');

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_pairing_requests');
    }
};
