<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('event_edition_id')->nullable()->constrained('event_editions')->nullOnDelete();
            $table->foreignId('event_race_id')->nullable()->constrained('event_races')->nullOnDelete();
            $table->foreignId('event_participant_id')->nullable()->constrained('event_participants')->nullOnDelete();
            $table->string('title');
            $table->string('event_name_manual')->nullable();
            $table->date('event_date')->nullable();
            $table->string('distance_label')->nullable();
            $table->string('official_time')->nullable();
            $table->string('pace')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->text('story')->nullable();
            $table->string('visibility')->default('public');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medals');
    }
};
