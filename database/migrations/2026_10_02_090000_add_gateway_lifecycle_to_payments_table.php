<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Give payments a lifecycle so a gateway payment can be created *before* it
     * is settled (pending -> paid/failed/cancelled) instead of being written
     * only once money has moved.
     *
     * Existing rows are backfilled to 'paid': they were all created by the
     * synchronous flow, i.e. they are all settled.
     */
    public function up()
    {
        // A gateway payment is created BEFORE it settles, so paid_at must be
        // nullable. It was originally `timestamp useCurrent()`, which silently
        // stamped "now" on pending rows and made the lifecycle unobservable.
        // doctrine/dbal is not installed, so the column is altered with SQL.
        DB::statement('ALTER TABLE payments MODIFY paid_at TIMESTAMP NULL');

        Schema::table('payments', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('payment_method');
            $table->string('paymongo_payment_intent_id')->nullable()->after('status');
            $table->string('paymongo_checkout_session_id')->nullable()->after('paymongo_payment_intent_id');
            $table->string('paymongo_reference_id')->nullable()->after('paymongo_checkout_session_id');
            $table->timestamp('failed_at')->nullable()->after('paid_at');
            $table->text('failure_message')->nullable()->after('failed_at');

            $table->index('status');
            $table->index('paymongo_payment_intent_id');
            $table->index('paymongo_checkout_session_id');
        });

        // Historical rows were all written after a successful deduction.
        DB::table('payments')->update(['status' => 'paid']);
    }

    public function down()
    {
        // Pending/failed rows have no paid_at; restore the NOT NULL default.
        DB::table('payments')->whereNull('paid_at')->update(['paid_at' => now()]);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['paymongo_payment_intent_id']);
            $table->dropIndex(['paymongo_checkout_session_id']);

            $table->dropColumn([
                'status',
                'paymongo_payment_intent_id',
                'paymongo_checkout_session_id',
                'paymongo_reference_id',
                'failed_at',
                'failure_message',
            ]);
        });

        DB::statement('ALTER TABLE payments MODIFY paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }
};