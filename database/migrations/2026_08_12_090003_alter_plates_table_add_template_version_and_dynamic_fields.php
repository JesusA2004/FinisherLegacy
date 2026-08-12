<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plates', function (Blueprint $table) {
            // The specific version rendered/printed — plate_template_id stays as a quick
            // reference to the template family, but this is the source of truth for
            // re-rendering: an old plate must keep using the version it was printed with.
            $table->foreignId('plate_template_version_id')->nullable()->after('plate_template_id')
                ->constrained('plate_template_versions')->nullOnDelete();
            // Superset snapshot of every dynamic field value resolved at generation time
            // (category, positions, splits, phrase, etc.) — the named columns above stay
            // for querying convenience, this is what the renderer actually reads from.
            $table->json('dynamic_fields')->nullable()->after('event_date');
        });
    }

    public function down(): void
    {
        Schema::table('plates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plate_template_version_id');
            $table->dropColumn('dynamic_fields');
        });
    }
};
