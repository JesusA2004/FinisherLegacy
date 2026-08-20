<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The row that makes "sync EVT-991 ten times → still the same
     * EventEdition" true (docs/adr/0005 §23). Only created at link time —
     * events the provider lists but the admin hasn't linked yet are never
     * persisted here.
     */
    public function up(): void
    {
        Schema::create('external_event_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_connection_id')->constrained('provider_connections')->cascadeOnDelete();
            $table->string('external_event_id');
            $table->foreignId('event_edition_id')->constrained('event_editions')->cascadeOnDelete();
            $table->string('cursor_before')->nullable();
            $table->string('cursor_after')->nullable();
            $table->boolean('live_sync_enabled')->default(false);
            $table->unsignedInteger('live_sync_interval_seconds')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['provider_connection_id', 'external_event_id'], 'ext_event_mappings_conn_ext_id_unique');
            $table->index('event_edition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_event_mappings');
    }
};
