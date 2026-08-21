<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The general-`/api/v1/*` equivalent of `device_idempotency_keys`
     * (Slice 1/2) — same shape, but keyed by a polymorphic actor
     * (`App\Models\User` or `App\Models\ProductionDevice`) instead of a
     * ProductionDevice-only column, since a User-authenticated API client
     * (e.g. a future Desktop/Mobile calling "generate plate") needs the
     * exact same replay protection a device write route already has. See
     * App\Http\Middleware\EnsureApiIdempotencyKey. Deliberately a separate
     * table rather than widening `device_idempotency_keys` — that table's
     * device-only shape is already shipped and tested (docs/adr/0002).
     */
    public function up(): void
    {
        Schema::create('api_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id');
            $table->string('route');
            $table->string('key');
            $table->unsignedSmallInteger('response_status');
            $table->json('response_body')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['actor_type', 'actor_id', 'route', 'key'], 'api_idempotency_keys_actor_route_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_idempotency_keys');
    }
};
