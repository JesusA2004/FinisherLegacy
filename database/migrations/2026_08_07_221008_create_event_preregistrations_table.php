<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_preregistrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_edition_id')->constrained('event_editions')->cascadeOnDelete();
            $table->foreignId('event_race_id')->constrained('event_races')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('bib_number')->nullable();
            $table->string('token')->unique();
            $table->string('qr_token')->unique();
            $table->string('status')->default('pending');
            // FK to event_participants added later (add_matched_participant_foreign migration)
            // once that table exists, avoiding a forward reference.
            $table->unsignedBigInteger('matched_participant_id')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->index(['event_edition_id', 'event_race_id']);
            $table->index('bib_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_preregistrations');
    }
};
