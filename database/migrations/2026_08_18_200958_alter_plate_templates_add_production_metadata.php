<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plate_templates', function (Blueprint $table) {
            // Which official preset this molde follows — purely descriptive,
            // used to offer the right starting layout, never changes rendering.
            $table->string('sport_type')->nullable()->after('material');
            // How the back face must be re-oriented in the jig after flipping
            // the plate — depends on the physical process, never guessed.
            $table->string('back_transform')->default('none')->after('sport_type');
            // Set only after a physical engraving test confirms a QR size
            // actually scans reliably. Null means "not validated yet" — see
            // docs/plate-production.md. Once set, a version can't be published
            // with a back QR smaller than this.
            $table->decimal('minimum_validated_qr_size_mm', 5, 2)->nullable()->after('back_transform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plate_templates', function (Blueprint $table) {
            $table->dropColumn(['sport_type', 'back_transform', 'minimum_validated_qr_size_mm']);
        });
    }
};
