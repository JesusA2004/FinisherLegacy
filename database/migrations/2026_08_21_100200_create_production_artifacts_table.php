<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per ProductionJob (front + back together — they're always
     * generated in the same atomic operation, see
     * App\Services\Production\ProductionArtifactService), never mutated
     * after `generated_at` is set. A reprint creates a NEW ProductionJob
     * and therefore a new artifact row — this table never has two live
     * rows for the same job, and an old row is never overwritten to
     * reflect a template/result change. See
     * docs/adr/0003-production-state-machine.md §Artifact.
     */
    public function up(): void
    {
        Schema::create('production_artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_job_id')->unique()->constrained('production_jobs')->cascadeOnDelete();
            $table->foreignId('plate_id')->constrained('plates')->cascadeOnDelete();
            $table->foreignId('plate_template_version_id')->constrained('plate_template_versions')->cascadeOnDelete();
            $table->string('renderer_version', 20);
            $table->string('format', 10)->default('svg');

            $table->string('front_storage_path');
            $table->string('front_sha256', 64);
            $table->string('back_storage_path');
            $table->string('back_sha256', 64);

            $table->decimal('width_mm', 6, 2)->nullable();
            $table->decimal('height_mm', 6, 2)->nullable();
            $table->string('back_transform', 20)->default('none');

            $table->json('metadata')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_artifacts');
    }
};
