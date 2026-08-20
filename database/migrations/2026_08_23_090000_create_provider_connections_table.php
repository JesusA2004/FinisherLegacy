<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `credentials`/`settings` are `encrypted`/`encrypted:array` casts on
     * the model (never plain columns read directly) — see
     * docs/adr/0005-unified-event-ingestion.md §Security. The provider
     * *implementation* (adapter class) is code/config, resolved by
     * `provider_key` through App\Services\Integrations\EventProviderRegistry;
     * this table only ever holds the *connection* (credentials, base URL,
     * test/sync bookkeeping).
     */
    public function up(): void
    {
        Schema::create('provider_connections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('provider_key');
            $table->string('name');
            $table->string('base_url')->nullable();
            $table->text('credentials')->nullable();
            $table->text('settings')->nullable();
            $table->string('status')->default('untested');
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_successful_sync_at')->nullable();
            $table->timestamps();

            $table->index('provider_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_connections');
    }
};
