<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_editions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('year');
            $table->date('event_date');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country');
            $table->string('timezone')->default('America/Mexico_City');
            $table->timestamp('registration_open_at')->nullable();
            $table->timestamp('registration_close_at')->nullable();
            $table->string('operation_mode')->default('hybrid');
            $table->string('status')->default('draft');
            $table->string('results_status')->default('pending');
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_editions');
    }
};
