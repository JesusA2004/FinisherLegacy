<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('medal_id')->nullable()->constrained('medals')->nullOnDelete();
            $table->foreignId('event_edition_id')->nullable()->constrained('event_editions')->nullOnDelete();
            $table->foreignId('event_participant_id')->nullable()->constrained('event_participants')->nullOnDelete();
            // Template used to render this plate. Not in the original table list, but
            // plate_templates would otherwise have nothing pointing to it.
            $table->foreignId('plate_template_id')->nullable()->constrained('plate_templates')->nullOnDelete();
            // FK to legacy_codes added later (add_legacy_code_foreign migration) since
            // legacy_codes.plate_id also points back here — one side has to go second.
            $table->unsignedBigInteger('legacy_code_id')->nullable();
            $table->string('serial_number')->unique();
            $table->string('generation_mode');
            // Snapshot fields: what was actually printed, kept even if the source
            // Participant/Result changes later.
            $table->string('athlete_name')->nullable();
            $table->string('bib_number')->nullable();
            $table->string('event_name')->nullable();
            $table->string('race_name')->nullable();
            $table->string('official_time')->nullable();
            $table->string('pace')->nullable();
            $table->date('event_date')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('linked_at')->nullable();
            $table->timestamp('produced_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plates');
    }
};
