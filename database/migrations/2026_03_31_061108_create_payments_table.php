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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paid_by')->constrained('users')->cascadeOnDelete();
            $table->string('transaction_id')->unique();
            $table->string('starting_point');
            $table->string('destination');
            $table->decimal('total_distance');
            $table->boolean('is_discounted')->default(false);
            $table->string('payment_method');
            $table->decimal('price');
            $table->timestamp('paid_at')->useCurrent();
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
        Schema::dropIfExists('payments');
    }
};
