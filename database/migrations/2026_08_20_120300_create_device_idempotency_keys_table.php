<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores the response for a device write request keyed by its
     * `Idempotency-Key` header, so a retried request (dropped connection,
     * desktop retry logic) replays the original result instead of
     * re-running the action. Scoped per device + route + key — never global.
     */
    public function up(): void
    {
        Schema::create('device_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_device_id')->constrained('production_devices')->cascadeOnDelete();
            $table->string('route');
            $table->string('key');
            $table->unsignedSmallInteger('response_status');
            $table->json('response_body')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['production_device_id', 'route', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_idempotency_keys');
    }
};
