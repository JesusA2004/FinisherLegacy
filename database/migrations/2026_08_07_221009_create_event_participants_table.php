<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_edition_id')->constrained('event_editions')->cascadeOnDelete();
            $table->foreignId('event_race_id')->constrained('event_races')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_participant_id')->nullable();
            $table->string('bib_number');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('category')->nullable();
            $table->string('registration_status')->default('registered');
            $table->string('source')->default('manual');
            $table->string('source_reference')->nullable();
            $table->string('verification_status')->default('unverified');
            $table->timestamps();

            $table->unique(['event_edition_id', 'bib_number']);
            $table->index(['event_edition_id', 'event_race_id']);
            $table->index(['event_edition_id', 'full_name']);
            $table->index('user_id');
        });

        Schema::table('event_preregistrations', function (Blueprint $table) {
            $table->foreign('matched_participant_id')->references('id')->on('event_participants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_preregistrations', function (Blueprint $table) {
            $table->dropForeign(['matched_participant_id']);
        });

        Schema::dropIfExists('event_participants');
    }
};
