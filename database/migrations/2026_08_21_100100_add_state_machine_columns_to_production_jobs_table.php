<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive columns for the Slice 2 physical-workflow state machine
     * (docs/adr/0003-production-state-machine.md). Does not touch or drop
     * the Slice 0 checklist columns (`front_engraved_at`, `back_engraved_at`,
     * `qr_verified_at`, `front_engraved_by`, `back_engraved_by`,
     * `qr_verified_by`) — those stay as-is, still populated by the new
     * Actions as evidence, per docs/adr/0003 §Actor.
     *
     * New `*_actor_type`/`*_actor_id` polymorphic pairs (not reusing
     * `front_engraved_by` etc., which are `belongsTo(User)`) are added only
     * for the four events the state machine itself gates on
     * (front/back/flip/qr) — delivered/failed/cancelled/released actors are
     * captured via ActivityLog's own polymorphic causer instead of a
     * dedicated column each, to avoid a column explosion for events the
     * FSM doesn't need to query.
     */
    public function up(): void
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->timestamp('preparation_started_at')->nullable()->after('completed_at');
            $table->timestamp('front_started_at')->nullable()->after('preparation_started_at');
            $table->timestamp('flip_confirmed_at')->nullable()->after('back_engraved_by');
            $table->timestamp('back_started_at')->nullable()->after('flip_confirmed_at');
            $table->timestamp('ready_at')->nullable()->after('qr_verified_by');
            $table->timestamp('delivered_at')->nullable()->after('ready_at');

            $table->string('front_actor_type')->nullable()->after('delivered_at');
            $table->unsignedBigInteger('front_actor_id')->nullable()->after('front_actor_type');
            $table->string('back_actor_type')->nullable()->after('front_actor_id');
            $table->unsignedBigInteger('back_actor_id')->nullable()->after('back_actor_type');
            $table->string('flip_actor_type')->nullable()->after('back_actor_id');
            $table->unsignedBigInteger('flip_actor_id')->nullable()->after('flip_actor_type');
            $table->string('qr_actor_type')->nullable()->after('flip_actor_id');
            $table->unsignedBigInteger('qr_actor_id')->nullable()->after('qr_actor_type');

            $table->string('qr_decoded_value')->nullable()->after('qr_actor_id');
            $table->string('error_code')->nullable()->after('error_message');

            // Which machine this job requires, if any — null means
            // generic/any station may claim it. See docs/adr/0003 §59.
            $table->foreignId('machine_profile_id')->nullable()->after('event_edition_id')
                ->constrained('machine_profiles')->nullOnDelete();

            $table->index(['front_actor_type', 'front_actor_id']);
            $table->index(['back_actor_type', 'back_actor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('machine_profile_id');
            $table->dropColumn([
                'preparation_started_at', 'front_started_at', 'flip_confirmed_at', 'back_started_at',
                'ready_at', 'delivered_at', 'qr_decoded_value', 'error_code',
                'front_actor_type', 'front_actor_id', 'back_actor_type', 'back_actor_id',
                'flip_actor_type', 'flip_actor_id', 'qr_actor_type', 'qr_actor_id',
            ]);
        });
    }
};
