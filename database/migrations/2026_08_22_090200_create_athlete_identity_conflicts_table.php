<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A "this might be the same person, a human should look" flag —
     * created whenever App\Services\Athletes\AthleteIdentityMatcher can't
     * confidently auto-link or confidently rule out a match. A lightweight
     * entity of its own (not EventIncident, which is about physical/event
     * operations, a different domain) — Slice 4's real provider imports
     * will create many more of these than Slice 3 does.
     *
     * `candidate_athlete_id` is the single best candidate for the UI's
     * "posible atleta" comparison; `candidates` (json) holds the full
     * ranked list for the AMBIGUOUS_NAME case (several plausible people),
     * where a single column isn't enough.
     */
    public function up(): void
    {
        Schema::create('athlete_identity_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_participant_id')->nullable()->constrained('event_participants')->nullOnDelete();
            $table->foreignId('candidate_athlete_id')->nullable()->constrained('athletes')->nullOnDelete();
            $table->json('candidates')->nullable();
            $table->string('source_type');
            $table->string('source_reference')->nullable();
            $table->json('incoming_data');
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->string('reason');
            $table->string('status')->default('pending');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('source_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athlete_identity_conflicts');
    }
};
