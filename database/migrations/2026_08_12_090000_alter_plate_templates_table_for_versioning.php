<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plate_templates', function (Blueprint $table) {
            // Pixel-based single-config columns from the pre-versioning prototype: no
            // production data depends on them yet, superseded by plate_template_versions.
            $table->dropColumn(['width', 'height', 'configuration']);

            $table->text('description')->nullable()->after('slug');
            $table->decimal('width_mm', 6, 2)->after('description');
            $table->decimal('height_mm', 6, 2)->after('width_mm');
            $table->string('material')->nullable()->after('height_mm');
            $table->string('orientation')->default('landscape')->after('material');
            $table->decimal('safe_margin_mm', 5, 2)->nullable()->after('orientation');
            $table->foreignId('created_by')->nullable()->after('safe_margin_mm')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('plate_templates', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['description', 'width_mm', 'height_mm', 'material', 'orientation', 'safe_margin_mm', 'created_by']);
            $table->unsignedInteger('width')->default(0);
            $table->unsignedInteger('height')->default(0);
            $table->json('configuration')->nullable();
        });
    }
};
