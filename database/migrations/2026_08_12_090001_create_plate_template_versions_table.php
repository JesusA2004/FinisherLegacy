<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plate_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plate_template_id')->constrained('plate_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            // Element trees for each face — see App\Support\PlateDynamicFields and
            // App\Services\PlateTemplateRenderService for the shape consumed here.
            $table->json('front_configuration');
            $table->json('back_configuration');
            $table->string('preview_front_path')->nullable();
            $table->string('preview_back_path')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['plate_template_id', 'version']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plate_template_versions');
    }
};
