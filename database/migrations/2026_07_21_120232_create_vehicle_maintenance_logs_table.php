<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicle_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('maintenance_task_id')->constrained('maintenance_tasks')->cascadeOnDelete();
            $table->date('service_date')->nullable();
            $table->integer('mileage_at_service')->nullable();
            $table->string('performed_by')->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehicle_maintenance_logs');
    }
};
