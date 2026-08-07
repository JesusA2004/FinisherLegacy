<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_participant_id')->unique()->constrained('event_participants')->cascadeOnDelete();
            $table->string('official_time')->nullable();
            $table->string('chip_time')->nullable();
            $table->string('pace')->nullable();
            $table->unsignedInteger('overall_position')->nullable();
            $table->unsignedInteger('gender_position')->nullable();
            $table->unsignedInteger('category_position')->nullable();
            $table->string('status')->default('pending');
            $table->string('result_source')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_results');
    }
};
