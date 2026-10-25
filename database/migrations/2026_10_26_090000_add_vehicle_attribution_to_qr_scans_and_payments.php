<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Which vehicle and driver a fare belongs to, found automatically.
     *
     * When a commuter scans a ride QR code the checkout page sends their position; the server matches it
     * to a vehicle that is broadcasting right there and records that vehicle, its driver on duty and how far
     * away the commuter was. The payment inherits both, so a fare is attributed to the right driver without
     * anyone typing it. The commuter's coordinates themselves are NOT stored.
     */
    public function up()
    {
        Schema::table('qr_scans', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('qr_code_id')->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->after('vehicle_id')->constrained('drivers')->nullOnDelete();
            $table->unsignedInteger('matched_distance_m')->nullable()->after('driver_id');
            $table->timestamp('located_at')->nullable()->after('matched_distance_m');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['driver_id']);
            $table->dropColumn(['vehicle_id', 'driver_id']);
        });

        Schema::table('qr_scans', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['driver_id']);
            $table->dropColumn(['vehicle_id', 'driver_id', 'matched_distance_m', 'located_at']);
        });
    }
};
