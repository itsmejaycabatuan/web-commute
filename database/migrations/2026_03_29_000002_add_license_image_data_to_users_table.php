<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores license photo bytes as base64 in MEDIUMTEXT (fits ~11MB raw image after encoding).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->mediumText('license_image_data')->nullable()->after('license_image_path');
            $table->string('license_image_mime', 100)->nullable()->after('license_image_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['license_image_data', 'license_image_mime']);
        });
    }
};
