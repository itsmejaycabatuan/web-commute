<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A QR code is just a code: one click generates it, and any active code works for any ride. It
     * needs no label and is not tied to a vehicle.
     */
    public function up()
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn(['label', 'vehicle_id']);
        });
    }

    public function down()
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->string('label')->default('Code')->after('token');
            $table->foreignId('vehicle_id')->nullable()->after('label')->constrained('vehicles')->nullOnDelete();
        });
    }
};
