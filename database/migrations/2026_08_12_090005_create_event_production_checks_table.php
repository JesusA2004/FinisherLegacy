<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_production_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_edition_id')->unique()->constrained('event_editions')->cascadeOnDelete();
            // The only checklist item that is a human attestation rather than something
            // derivable from existing data (template assigned, version published, etc.).
            $table->timestamp('qr_tested_at')->nullable();
            $table->foreignId('qr_tested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_production_checks');
    }
};
