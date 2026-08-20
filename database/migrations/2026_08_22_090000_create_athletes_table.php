<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The canonical sporting identity — a real person, independent of
     * whether they ever created a Finisher Legacy account. See
     * docs/adr/0004-athlete-canonical-identity.md.
     *
     * `user_id` is nullable+unique: an Athlete can exist with no User
     * (imported from an event roster), and a User can have at most one
     * Athlete — both directions of the 1:1 rule are enforced by this one
     * column being unique, no need for a second FK on `users`.
     *
     * `normalized_*` columns are NOT the display value — see
     * App\Support\Athletes\AthleteIdentityNormalizer. They exist purely so
     * App\Services\Athletes\AthleteIdentityMatcher can do indexed exact-match
     * lookups instead of scanning+normalizing every row per import.
     *
     * No PII beyond what §7 asks for — no address, no ID document, no
     * payment info.
     */
    public function up(): void
    {
        Schema::create('athletes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name');

            $table->string('normalized_first_name');
            $table->string('normalized_last_name');
            $table->string('normalized_full_name');

            $table->string('email')->nullable();
            $table->string('normalized_email')->nullable();

            $table->string('phone')->nullable();
            $table->string('normalized_phone')->nullable();

            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('country')->nullable();

            $table->string('identity_status')->default('active');
            $table->foreignId('merged_into_athlete_id')->nullable()->constrained('athletes')->nullOnDelete();

            $table->timestamps();

            $table->index('normalized_email');
            $table->index('normalized_phone');
            $table->index('normalized_full_name');
            $table->index('identity_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athletes');
    }
};
