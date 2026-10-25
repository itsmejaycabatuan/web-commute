<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A seat on a vehicle that lost its driver (the driver was suspended, or their licence expired) and
     * is waiting for a replacement. The system fills it by itself when a free driver exists, and keeps
     * the row open, with a reminder to the Maintenance Managers, until one does.
     */
    public function up()
    {
        Schema::create('driver_vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('former_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('reason', 40); // suspended | licence_expired
            // How many drivers the vehicle should have again: the vacancy is only filled while it has fewer.
            $table->unsignedTinyInteger('target_drivers')->default(1);
            $table->timestamp('opened_at');
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution', 40)->nullable(); // filled | not_needed
            $table->foreignId('replacement_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->index(['resolved_at', 'vehicle_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('driver_vacancies');
    }
};
