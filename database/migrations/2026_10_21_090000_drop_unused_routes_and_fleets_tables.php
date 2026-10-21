<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * `routes` and `fleets` were created early on and never used: no model, controller,
     * view, seeder or test reads or writes them (vehicles live in `vehicles`).
     * The "… routes" permission names in RolePermissionSeeder are plain strings, not this table.
     */
    public function up()
    {
        Schema::dropIfExists('fleets');
        Schema::dropIfExists('routes');
    }

    /**
     * Recreates both tables exactly as 2026_06_15_131531 and 2026_06_27_054325 defined them
     * (empty — rows are not restored).
     */
    public function down()
    {
        if (! Schema::hasTable('routes')) {
            Schema::create('routes', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('description');
                $table->decimal('total_distance');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fleets')) {
            Schema::create('fleets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('vehicle_id')->unique();
                $table->string('make');
                $table->string('model');
                $table->integer('year');
                $table->string('fuel_type');
                $table->string('class');
                $table->string('plate_number');
                $table->text('notes');
                $table->timestamps();
            });
        }
    }
};
