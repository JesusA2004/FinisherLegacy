<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_editions', function (Blueprint $table) {
            $table->string('production_export_format')->default('svg')->after('results_status');
            $table->unsignedSmallInteger('default_dpi')->default(300)->after('production_export_format');
        });
    }

    public function down(): void
    {
        Schema::table('event_editions', function (Blueprint $table) {
            $table->dropColumn(['production_export_format', 'default_dpi']);
        });
    }
};
