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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('driver_code')->unique()->nullable();
            $table->string('name')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('contact_info')->nullable();
            $table->string('license_number')->nullable();
            $table->string('license_code')->nullable();
            $table->string('license_image_path')->nullable();
            $table->string('license_status')->nullable();
            $table->boolean('is_approved')->default(0);
            $table->boolean('is_rejected')->default(0);
            $table->mediumText('license_image_data')->nullable();
            $table->string('license_image_mime', 100)->nullable();
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
        Schema::dropIfExists('drivers');
    }
};
