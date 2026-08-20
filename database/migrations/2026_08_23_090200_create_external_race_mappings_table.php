<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_race_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_connection_id')->constrained('provider_connections')->cascadeOnDelete();
            $table->string('external_event_id');
            $table->string('external_race_id');
            $table->foreignId('event_race_id')->constrained('event_races')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['provider_connection_id', 'external_race_id'], 'ext_race_mappings_conn_ext_id_unique');
            $table->index('event_race_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_race_mappings');
    }
};
