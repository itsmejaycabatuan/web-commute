<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Two drivers can share a vehicle, taking turns day by day.
     *
     *  - `driver_vehicle`: which drivers are assigned to a vehicle (at most two per vehicle, enforced
     *    when assigning; a driver still has one vehicle, hence the unique driver_id). It replaces
     *    `vehicles.driver_id`.
     *  - `vehicle_duty_days`: the Maintenance Manager's roster — which of the vehicle's drivers drives
     *    it on a given date. A vehicle with two drivers can only be driven on a day the roster gives
     *    to that driver.
     *  - `time_keepings.vehicle_id`: the vehicle a shift was actually worked on.
     */
    public function up()
    {
        Schema::create('driver_vehicle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('driver_id');
            $table->index('vehicle_id');
        });

        DB::statement('
            INSERT INTO driver_vehicle (vehicle_id, driver_id, created_at, updated_at)
            SELECT id, driver_id, NOW(), NOW() FROM vehicles WHERE driver_id IS NOT NULL
        ');

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn('driver_id');
        });

        Schema::create('vehicle_duty_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->date('duty_date');
            $table->timestamps();

            $table->unique(['vehicle_id', 'duty_date']);
            $table->index(['driver_id', 'duty_date']);
        });

        Schema::table('time_keepings', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('driver_id')->constrained('vehicles')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('time_keepings', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn('vehicle_id');
        });

        Schema::dropIfExists('vehicle_duty_days');

        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->after('id')->constrained('drivers')->cascadeOnDelete();
        });

        DB::statement('
            UPDATE vehicles v JOIN driver_vehicle dv ON dv.vehicle_id = v.id SET v.driver_id = dv.driver_id
        ');

        Schema::dropIfExists('driver_vehicle');
    }
};
