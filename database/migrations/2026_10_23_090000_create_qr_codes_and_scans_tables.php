<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ride QR codes. The Admin generates them and they are put up inside the PUJ; a commuter has to scan
     * one before paying a fare, so the fare is paid on board rather than beforehand. Any active code
     * made by the system will do (it is not tied to one vehicle), and each scan is kept so a payment can
     * show which code it was made against.
     */
    public function up()
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('label');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('qr_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('qr_code_id')->nullable()->constrained('qr_codes')->nullOnDelete();
            $table->timestamp('scanned_at');
            // Set when the scan has been used for a payment: one scan covers one fare.
            $table->timestamp('used_at')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'used_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('qr_scans');
        Schema::dropIfExists('qr_codes');
    }
};
