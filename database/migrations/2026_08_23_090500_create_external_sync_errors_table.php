<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Row-level isolation (docs/adr/0005 §37): one bad participant/result
     * lands here and the sync keeps going. `context` is a scrubbed json
     * blob — never the raw provider payload, and never a token/api key.
     */
    public function up(): void
    {
        Schema::create('external_sync_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_run_id')->constrained('external_sync_runs')->cascadeOnDelete();
            $table->string('entity_type');
            $table->string('external_id')->nullable();
            $table->string('code');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['sync_run_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_sync_errors');
    }
};
