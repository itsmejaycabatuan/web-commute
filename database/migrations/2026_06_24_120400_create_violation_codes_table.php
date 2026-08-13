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
        Schema::create('violation_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('violation_name');
            $table->decimal('first_offense');
            $table->decimal('second_offense');
            $table->decimal('third_offense');
            $table->decimal('fourth_offense')->nullable();
            $table->boolean('is_revoked')->default(false);
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
        Schema::dropIfExists('violation_codes');
    }
};
