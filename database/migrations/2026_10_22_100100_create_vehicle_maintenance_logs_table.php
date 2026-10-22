<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Bring back the vehicle maintenance log.
     *
     * `preventive_maintenances` keeps one row per vehicle/task — the *latest*
     * service — so logging the next one overwrites the last. The log is the
     * append-only history: every service logged from the preventive-maintenance
     * screen adds a row here and none is ever replaced.
     *
     * A log outlives the schedule entry it came from (`maintenance_id` is cleared,
     * not cascaded), and is removed with its vehicle or task.
     */
    public function up()
    {
        Schema::create('vehicle_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_id')->nullable()->constrained('preventive_maintenances')->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('maintenance_task_id')->constrained('maintenance_tasks')->cascadeOnDelete();
            $table->date('service_date');
            $table->unsignedInteger('mileage_at_service');
            $table->string('performed_by')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('invoice_number', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'service_date']);
        });

        // The services already on the schedule become the first entries of the log.
        DB::statement('
            INSERT INTO vehicle_maintenance_logs
                (maintenance_id, vehicle_id, maintenance_task_id, service_date, mileage_at_service, cost, remarks, created_at, updated_at)
            SELECT id, vehicle_id, task_id, last_service_date, COALESCE(last_service_odo, 0), COALESCE(last_service_cost, 0), comments, NOW(), NOW()
            FROM preventive_maintenances
            WHERE last_service_date IS NOT NULL
        ');
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_maintenance_logs');
    }
};
