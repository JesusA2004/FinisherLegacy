<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive — `first_name`/`last_name`/`email`/`phone`/`birth_date`/
     * `category`/`bib_number`/`user_id` all stay exactly as they are. They
     * are the event's snapshot of what was reported for this participation
     * (docs/adr/0004 §Snapshot vs. identity); `athlete_id` is the new
     * canonical-identity pointer, deliberately nullable — a participant can
     * exist in `possible_duplicate`/conflict limbo with no athlete linked
     * yet (see App\Models\AthleteIdentityConflict).
     */
    public function up(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->foreignId('athlete_id')->nullable()->after('user_id')->constrained('athletes')->nullOnDelete();
            $table->index('athlete_id');
        });
    }

    public function down(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('athlete_id');
        });
    }
};
