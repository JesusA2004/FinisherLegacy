<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive claim/lease metadata for the Device API (ADR 0002, Slice 1).
     * Deliberately does NOT touch `status` (App\Enums\ProductionJobStatus)
     * or `assigned_user_id` — those stay human-oriented (ProductionService)
     * for this slice. A claimed job's coarse status is still whatever the
     * existing kanban logic already set; "which device, since when, until
     * when" is orthogonal metadata layered on top, not a new state machine.
     * See docs/adr/0002 §Deuda for the Slice 2 plan to unify these.
     */
    public function up(): void
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->foreignId('production_device_id')->nullable()->after('assigned_user_id')
                ->constrained('production_devices')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable()->after('production_device_id');
            $table->timestamp('lease_expires_at')->nullable()->after('claimed_at');

            $table->index(['status', 'production_device_id']);
        });
    }

    public function down(): void
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_device_id');
            $table->dropColumn(['claimed_at', 'lease_expires_at']);
        });
    }
};
