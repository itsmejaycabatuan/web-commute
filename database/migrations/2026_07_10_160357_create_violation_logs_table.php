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
        Schema::create('violation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vc_id')->constrained('violation_codes')->cascadeOnDelete();
            $table->string('violation_instance');
            $table->decimal('violation_fine');
            $table->string('additional_penalties')->nullable();
            $table->date('date_of_violation');
            $table->time('time_of_violation');
            $table->string('place_of_violation');
            $table->string('remarks');
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
        Schema::dropIfExists('violation_logs');
    }
};
