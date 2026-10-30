<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The discounted fare was never used by the pay-fare flow (only the regular
 * fare is charged), so the per-tier discount amounts are dropped.
 * payments.is_discounted stays: it is history on already-recorded payments.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('fare_rates', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }

    public function down()
    {
        Schema::table('fare_rates', function (Blueprint $table) {
            // The original amounts are not recoverable.
            $table->double('discount')->default(0)->after('regular');
        });
    }
};
