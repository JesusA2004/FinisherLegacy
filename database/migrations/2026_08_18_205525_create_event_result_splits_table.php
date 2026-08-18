<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deliberately flexible instead of fixed swim/bike/run columns on
     * event_results — running events need 5K/10K/21K/30K splits, cycling
     * needs laps, trail needs checkpoints, triathlon needs swim/bike/run.
     * `type` stays a plain string (not an enum) so a new discipline never
     * needs a migration; PlateRenderData::fromPlate() is what maps
     * type=swim/bike/run back onto the swim_time/bike_time/run_time
     * template fields templates already expect, so existing molds don't
     * need to know this table exists.
     */
    public function up(): void
    {
        Schema::create('event_result_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_result_id')->constrained('event_results')->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->string('label');
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->decimal('distance_value', 8, 3)->nullable();
            $table->string('distance_unit', 10)->nullable();
            $table->string('segment_time')->nullable();
            $table->string('elapsed_time')->nullable();
            $table->string('pace')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['event_result_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_result_splits');
    }
};
