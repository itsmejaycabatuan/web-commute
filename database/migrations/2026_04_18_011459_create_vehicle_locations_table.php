<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicle_locations', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->decimal('latitude', 11, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy')->nullable();
            $table->decimal('speed')->nullable();
            $table->timestamp('last_update');
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
        Schema::dropIfExists('vehicle_locations');
    }
};
