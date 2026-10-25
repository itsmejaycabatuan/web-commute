<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A hand-off between the desktop and a phone while a vehicle is being added. The Maintenance Manager's
     * browser opens a session and shows its link as a QR code; the phone opens the link (the random token is the
     * only credential it needs) and uploads photos of the OR/CR; the server reads them and the browser, which is
     * polling the session, receives the values to check. Photos live only as long as the session.
     */
    public function up()
    {
        Schema::create('vehicle_scan_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending | processing | ready | failed | consumed | cancelled
            $table->timestamp('expires_at');
            $table->json('files')->nullable();   // paths on the private disk
            $table->json('result')->nullable();  // fields, warnings, documents
            $table->string('error')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_scan_sessions');
    }
};
