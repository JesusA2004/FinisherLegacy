<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('provider_connection_id')->constrained('provider_connections')->cascadeOnDelete();
            $table->foreignId('event_edition_id')->nullable()->constrained('event_editions')->nullOnDelete();
            $table->string('sync_type');
            $table->string('status')->default('pending');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->unsignedInteger('events_received')->default(0);
            $table->unsignedInteger('participants_received')->default(0);
            $table->unsignedInteger('participants_created')->default(0);
            $table->unsignedInteger('participants_updated')->default(0);
            $table->unsignedInteger('results_received')->default(0);
            $table->unsignedInteger('results_created')->default(0);
            $table->unsignedInteger('results_updated')->default(0);
            $table->unsignedInteger('splits_received')->default(0);
            $table->unsignedInteger('identity_conflicts')->default(0);
            $table->unsignedInteger('errors_count')->default(0);

            $table->string('cursor_before')->nullable();
            $table->string('cursor_after')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['provider_connection_id', 'status']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_sync_runs');
    }
};
