<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Preventive maintenance now holds the service date, odometer reading and
     * cost of every task, so the separate vehicle maintenance log is retired.
     */
    public function up()
    {
        Schema::dropIfExists('vehicle_maintenance_logs');
    }

    public function down()
    {
        Schema::create('vehicle_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_id')->nullable()->constrained('preventive_maintenances')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable();
            $table->foreignId('maintenance_task_id')->nullable();
            $table->date('service_date')->nullable();
            $table->unsignedInteger('mileage_at_service')->nullable();
            $table->string('performed_by')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('invoice_number')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }
};
