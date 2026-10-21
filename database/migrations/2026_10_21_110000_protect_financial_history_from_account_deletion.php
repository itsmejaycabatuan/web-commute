<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Deleting a user used to cascade straight through their payments and top-ups, so a commuter
     * who deleted their account erased their own payment history (and the ledger the Admin audits).
     *
     * Now:
     *  - users.anonymised_at marks an account whose personal data was removed but whose financial
     *    rows were kept (App\Models\User::eraseAccount());
     *  - payments and top-up history can no longer be removed by deleting their user or wallet:
     *    those foreign keys RESTRICT, so a delete that would destroy them fails loudly instead of
     *    silently succeeding. Everything else still cascades (wallet, preferences, location rows…).
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('anonymised_at')->nullable()->after('suspended_at');
            $table->index('anonymised_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign('payments_paid_by_foreign');
            $table->foreign('paid_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('topup_histories', function (Blueprint $table) {
            $table->dropForeign('topup_histories_user_id_foreign');
            $table->dropForeign('topup_histories_wallet_id_foreign');
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('wallet_id')->references('id')->on('wallets')->restrictOnDelete();
        });
    }

    public function down()
    {
        Schema::table('topup_histories', function (Blueprint $table) {
            $table->dropForeign('topup_histories_user_id_foreign');
            $table->dropForeign('topup_histories_wallet_id_foreign');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign('payments_paid_by_foreign');
            $table->foreign('paid_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['anonymised_at']);
            $table->dropColumn('anonymised_at');
        });
    }
};
