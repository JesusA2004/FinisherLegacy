<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_races', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_edition_id')->constrained('event_editions')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('distance_value', 8, 3)->nullable();
            $table->string('distance_unit')->nullable();
            $table->string('race_type')->nullable();
            $table->time('start_time')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['event_edition_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_races');
    }
};
