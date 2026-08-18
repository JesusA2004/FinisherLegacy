<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A "machine profile" is a workflow label, not a driver — Finisher
     * Legacy never talks to the laser directly (see docs/plate-production.md).
     * Deliberately excludes power/speed/frequency: those depend on material,
     * finish, fiber source and lens, and must be calibrated physically per
     * machine — never shipped as a universal default.
     */
    public function up(): void
    {
        Schema::create('machine_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('software')->nullable();
            $table->string('default_format', 10)->default('svg');
            $table->decimal('width_mm', 6, 2)->nullable();
            $table->decimal('height_mm', 6, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_profiles');
    }
};
