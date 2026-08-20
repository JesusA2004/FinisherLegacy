<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `manual_override_fields` lists which columns are locked against the
     * next provider sync (docs/adr/0005 §97-99) — e.g.
     * `["official_time"]`. An admin correction to one field never locks the
     * whole row; `IngestEventResult` skips exactly the listed fields and
     * still applies everything else the provider sends.
     */
    public function up(): void
    {
        Schema::table('event_results', function (Blueprint $table) {
            $table->timestamp('manual_override_at')->nullable()->after('verified_at');
            $table->foreignId('manual_override_by')->nullable()->after('manual_override_at')->constrained('users')->nullOnDelete();
            $table->json('manual_override_fields')->nullable()->after('manual_override_by');
        });
    }

    public function down(): void
    {
        Schema::table('event_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manual_override_by');
            $table->dropColumn(['manual_override_at', 'manual_override_fields']);
        });
    }
};
