<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-plate production checklist (§33-34 of the two-face rules):
     * three quick touch-friendly toggles an operator taps at the machine,
     * separate from the coarse kanban status. Timestamps + who did it,
     * not just booleans, since "who verified the QR" is worth auditing.
     */
    public function up(): void
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->timestamp('front_engraved_at')->nullable()->after('completed_at');
            $table->foreignId('front_engraved_by')->nullable()->after('front_engraved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('back_engraved_at')->nullable()->after('front_engraved_by');
            $table->foreignId('back_engraved_by')->nullable()->after('back_engraved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('qr_verified_at')->nullable()->after('back_engraved_by');
            $table->foreignId('qr_verified_by')->nullable()->after('qr_verified_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('front_engraved_by');
            $table->dropConstrainedForeignId('back_engraved_by');
            $table->dropConstrainedForeignId('qr_verified_by');
            $table->dropColumn(['front_engraved_at', 'back_engraved_at', 'qr_verified_at']);
        });
    }
};
