<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Give the maintenance *log* its own schema.
     *
     * The table was originally created as a bare pointer (id, maintenance_id,
     * timestamps) while `preventive_maintenances` held the service details — but
     * the application writes service logs with their own vehicle, task, date,
     * mileage, cost and invoice, and queries them by those columns (fleet cost
     * per km, per-vehicle history, report "completed" counts). So the log becomes
     * a first-class record with its own columns, and keeps a nullable link back
     * to the preventive-maintenance schedule entry it was logged from.
     *
     * Existing rows are backfilled from the schedule they point at before the
     * columns are tightened.
     */
    public function up()
    {
        Schema::table('vehicle_maintenance_logs', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('maintenance_id');
            $table->foreignId('maintenance_task_id')->nullable()->after('vehicle_id');
            $table->date('service_date')->nullable()->after('maintenance_task_id');
            $table->unsignedInteger('mileage_at_service')->nullable()->after('service_date');
            $table->string('performed_by')->nullable()->after('mileage_at_service');
            $table->decimal('cost', 12, 2)->nullable()->after('performed_by');
            $table->string('invoice_number')->nullable()->after('cost');
            $table->text('remarks')->nullable()->after('invoice_number');
        });

        // Backfill from the preventive-maintenance entry each log points at.
        DB::statement('
            UPDATE vehicle_maintenance_logs l
            JOIN preventive_maintenances pm ON pm.id = l.maintenance_id
            SET
                l.vehicle_id = pm.vehicle_id,
                l.maintenance_task_id = pm.task_id,
                l.service_date = pm.last_service_date,
                l.mileage_at_service = pm.last_service_odo,
                l.performed_by = COALESCE(l.performed_by, "Preventive Maintenance"),
                l.cost = COALESCE(l.cost, pm.last_service_cost)
        ');

        // Any leftover row without a schedule entry is detached rather than kept
        // in a state that cannot satisfy the new constraints.
        DB::table('vehicle_maintenance_logs')->whereNull('vehicle_id')->delete();

        DB::statement('
            ALTER TABLE vehicle_maintenance_logs
            MODIFY vehicle_id BIGINT UNSIGNED NOT NULL,
            MODIFY maintenance_task_id BIGINT UNSIGNED NOT NULL,
            MODIFY service_date DATE NOT NULL,
            MODIFY mileage_at_service INT UNSIGNED NOT NULL,
            MODIFY performed_by VARCHAR(255) NOT NULL,
            MODIFY cost DECIMAL(12,2) NOT NULL DEFAULT 0
        ');

        Schema::table('vehicle_maintenance_logs', function (Blueprint $table) {
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            $table->foreign('maintenance_task_id')->references('id')->on('maintenance_tasks')->cascadeOnDelete();

            $table->index(['vehicle_id', 'service_date']);
            $table->index('maintenance_task_id');
        });

        // A log may outlive the schedule entry it was created from, so the link
        // becomes nullable and is cleared rather than cascading a delete.
        DB::statement('ALTER TABLE vehicle_maintenance_logs MODIFY maintenance_id BIGINT UNSIGNED NULL');

        Schema::table('vehicle_maintenance_logs', function (Blueprint $table) {
            $table->dropForeign(['maintenance_id']);
            $table->foreign('maintenance_id')->references('id')->on('preventive_maintenances')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('vehicle_maintenance_logs', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['maintenance_task_id']);
            $table->dropForeign(['maintenance_id']);
            $table->dropIndex(['vehicle_id', 'service_date']);
            $table->dropIndex(['maintenance_task_id']);

            $table->dropColumn([
                'vehicle_id',
                'maintenance_task_id',
                'service_date',
                'mileage_at_service',
                'performed_by',
                'cost',
                'invoice_number',
                'remarks',
            ]);
        });

        Schema::table('vehicle_maintenance_logs', function (Blueprint $table) {
            $table->foreign('maintenance_id')->references('id')->on('preventive_maintenances')->cascadeOnDelete();
        });
    }
};