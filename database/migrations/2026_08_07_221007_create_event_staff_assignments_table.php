<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Not part of the table list the product spec enumerated, but required to make
     * "event_manager: administra eventos asignados" and operator/production scoping
     * enforceable: without it there is no record of which staff user is assigned to
     * which event edition, so Policies would have no way to check "is this event
     * assigned to me".
     */
    public function up(): void
    {
        Schema::create('event_staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_edition_id')->constrained('event_editions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();

            $table->unique(['event_edition_id', 'user_id', 'role'], 'event_staff_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_staff_assignments');
    }
};
