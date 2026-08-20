<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive, for historical queries that shouldn't always have to go
     * through User (App\Queries\Athletes\GetAthleteHistory) — `user_id`
     * stays required as-is, unchanged.
     */
    public function up(): void
    {
        Schema::table('medals', function (Blueprint $table) {
            $table->foreignId('athlete_id')->nullable()->after('user_id')->constrained('athletes')->nullOnDelete();
            $table->index('athlete_id');
        });
    }

    public function down(): void
    {
        Schema::table('medals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('athlete_id');
        });
    }
};
