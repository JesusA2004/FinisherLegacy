<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Slice 6 DB index audit (docs/api/v1.md §Performance) — `production_jobs`
     * already indexed `status` and `(status, production_device_id)`, but
     * every "next job for THIS event" query
     * (App\Services\Devices\ProductionJobClaimService::availableJobsQuery(),
     * App\Queries\Operations\GetEventOperationsDashboard::productionStatus())
     * filters by `event_edition_id` + `status` together — a composite
     * covers that pattern directly instead of relying on the `status`
     * index alone plus a row-by-row filter.
     */
    public function up(): void
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->index(['event_edition_id', 'status'], 'production_jobs_event_edition_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->dropIndex('production_jobs_event_edition_status_index');
        });
    }
};
