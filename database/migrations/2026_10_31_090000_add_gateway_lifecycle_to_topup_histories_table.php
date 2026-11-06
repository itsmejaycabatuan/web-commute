<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A GCash / Maya top-up goes through PayMongo, so it exists (as `pending`) before any money has moved and
     * only credits the wallet once the gateway confirms payment. The row therefore needs the gateway ids to be
     * found again by the webhook, the return URL and the reconciliation job, plus the lifecycle timestamps.
     *
     * Every timestamp is nullable on purpose: a bare NOT NULL `timestamp` column would silently get
     * ON UPDATE CURRENT_TIMESTAMP on this MariaDB (see 2026_10_28_100000_stop_timestamps_updating_themselves).
     *
     * Existing rows were all credited at once, so they are stamped paid at the time they were created.
     */
    public function up()
    {
        Schema::table('topup_histories', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->unique()->after('wallet_id');
            $table->string('paymongo_payment_intent_id')->nullable()->after('status')->index();
            $table->string('paymongo_checkout_session_id')->nullable()->after('paymongo_payment_intent_id')->index();
            $table->timestamp('paid_at')->nullable()->after('paymongo_checkout_session_id');
            $table->timestamp('failed_at')->nullable()->after('paid_at');
            $table->text('failure_message')->nullable()->after('failed_at');
        });

        DB::table('topup_histories')->where('status', 'completed')->update(['paid_at' => DB::raw('created_at')]);
    }

    public function down()
    {
        Schema::table('topup_histories', function (Blueprint $table) {
            $table->dropUnique(['transaction_id']);
            $table->dropIndex(['paymongo_payment_intent_id']);
            $table->dropIndex(['paymongo_checkout_session_id']);

            $table->dropColumn([
                'transaction_id',
                'paymongo_payment_intent_id',
                'paymongo_checkout_session_id',
                'paid_at',
                'failed_at',
                'failure_message',
            ]);
        });
    }
};
