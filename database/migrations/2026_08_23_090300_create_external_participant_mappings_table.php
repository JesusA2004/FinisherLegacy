<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separate from `event_participants.external_participant_id` because a
     * single event can have roster from one provider and results from
     * another (docs/adr/0005 §94) — a single column on event_participants
     * couldn't hold two providers' IDs for the same row. `external_athlete_id`
     * is stored here too (not just on AthleteExternalIdentity) so a result
     * that references only an athlete id — no participant id — can still be
     * resolved without a join through Athlete.
     */
    public function up(): void
    {
        Schema::create('external_participant_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_connection_id')->constrained('provider_connections')->cascadeOnDelete();
            $table->string('external_participant_id');
            $table->string('external_athlete_id')->nullable();
            $table->foreignId('event_participant_id')->constrained('event_participants')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['provider_connection_id', 'external_participant_id'], 'ext_participant_mappings_conn_ext_id_unique');
            $table->index('event_participant_id');
            $table->index(['provider_connection_id', 'external_athlete_id'], 'ext_participant_mappings_conn_athlete_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_participant_mappings');
    }
};
