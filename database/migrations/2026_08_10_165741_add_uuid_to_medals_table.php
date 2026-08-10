<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Medals need a public identifier that isn't the incrementing primary
     * key — the API contract (and any future public medal link) must not
     * leak/depend on row order. Backfills existing rows in the same
     * migration since this is still pilot-scale data.
     */
    public function up(): void
    {
        Schema::table('medals', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('medals')->orderBy('id')->select('id')->chunkById(500, function ($medals) {
            foreach ($medals as $medal) {
                DB::table('medals')->where('id', $medal->id)->update(['uuid' => (string) Str::uuid()]);
            }
        });

        Schema::table('medals', function (Blueprint $table) {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('medals', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
