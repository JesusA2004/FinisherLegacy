<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_imports', function (Blueprint $table) {
            $table->json('column_mapping')->nullable()->after('original_filename');
        });
    }

    public function down(): void
    {
        Schema::table('event_imports', function (Blueprint $table) {
            $table->dropColumn('column_mapping');
        });
    }
};
