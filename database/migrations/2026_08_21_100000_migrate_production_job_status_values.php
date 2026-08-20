<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rewrites the Slice 1 coarse `processing`/`completed` values to the
     * Slice 2 granular state machine (App\Enums\ProductionJobStatus) so no
     * existing row is left holding a string the enum no longer recognizes.
     *
     * `processing` -> `preparing`: the only physical-work state Slice 1
     * had. A job actually mid-engrave under the old coarse model has no
     * way to know which sub-step it was on, so it restarts at the first
     * one — safe because Slice 1 never had real hardware attached (no
     * physical evidence to contradict).
     *
     * `completed` -> `delivered` if its Plate already reached `delivered`;
     * otherwise -> `ready`. Both are terminal-ish success states in the
     * new model, so this never loses information the coarse status had.
     */
    public function up(): void
    {
        DB::table('production_jobs')->where('status', 'processing')->update(['status' => 'preparing']);

        DB::table('production_jobs')
            ->join('plates', 'plates.id', '=', 'production_jobs.plate_id')
            ->where('production_jobs.status', 'completed')
            ->where('plates.status', 'delivered')
            ->update(['production_jobs.status' => 'delivered']);

        DB::table('production_jobs')->where('status', 'completed')->update(['status' => 'ready']);
    }

    public function down(): void
    {
        DB::table('production_jobs')->whereIn('status', ['preparing', 'assigned'])->update(['status' => 'processing']);
        DB::table('production_jobs')->whereIn('status', ['ready', 'delivered'])->update(['status' => 'completed']);
    }
};
