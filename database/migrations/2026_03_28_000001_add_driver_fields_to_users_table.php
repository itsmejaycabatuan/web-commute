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
        Schema::table('users', function (Blueprint $table) {
            $table->string('license_number')->nullable()->after('password');
            $table->string('license_code')->nullable()->after('license_number');
            $table->string('license_image_path')->nullable()->after('license_code');
            $table->string('driver_approval_status')->nullable()->after('license_image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'license_number',
                'license_code',
                'license_image_path',
                'driver_approval_status',
            ]);
        });
    }
};
