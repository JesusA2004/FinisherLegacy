<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive. `user_id` stays — see docs/adr/0004 §User_id legacy. An
     * integrated Plate copies `event_participant.athlete_id` at generation
     * time (App\Services\PlateGenerationService); a quick Plate's
     * `athlete_id` stays null until someone claims its Legacy Code.
     */
    public function up(): void
    {
        Schema::table('plates', function (Blueprint $table) {
            $table->foreignId('athlete_id')->nullable()->after('user_id')->constrained('athletes')->nullOnDelete();
            $table->index('athlete_id');
        });
    }

    public function down(): void
    {
        Schema::table('plates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('athlete_id');
        });
    }
};
