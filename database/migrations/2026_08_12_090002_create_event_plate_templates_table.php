<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_plate_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_edition_id')->constrained('event_editions')->cascadeOnDelete();
            // Null = applies to the whole edition. Set = overrides for one race/distance
            // (21K vs 42K having different molds), per the pilot's "start simple" rule.
            $table->foreignId('event_race_id')->nullable()->constrained('event_races')->cascadeOnDelete();
            $table->foreignId('plate_template_version_id')->constrained('plate_template_versions')->restrictOnDelete();
            $table->boolean('is_default')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['event_edition_id', 'event_race_id', 'active'], 'ept_edition_race_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_plate_templates');
    }
};
