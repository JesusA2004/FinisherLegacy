<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->uuid('uuid')->unique();
            $table->foreignId('plate_id')->nullable()->constrained('plates')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('medal_id')->nullable()->constrained('medals')->nullOnDelete();
            $table->string('status')->default('generated');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->foreignId('claimed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::table('plates', function (Blueprint $table) {
            $table->foreign('legacy_code_id')->references('id')->on('legacy_codes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('plates', function (Blueprint $table) {
            $table->dropForeign(['legacy_code_id']);
        });

        Schema::dropIfExists('legacy_codes');
    }
};
