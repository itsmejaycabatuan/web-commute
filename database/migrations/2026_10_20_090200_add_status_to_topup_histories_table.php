<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Every top-up carries a payment status so the admin ledger can list and
     * filter all of them, not only the completed ones. Existing rows were all
     * applied to the wallet, so they are backfilled as completed.
     */
    public function up()
    {
        Schema::table('topup_histories', function (Blueprint $table) {
            $table->string('status', 20)->default('completed')->after('payment_method')->index();
        });
    }

    public function down()
    {
        Schema::table('topup_histories', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
