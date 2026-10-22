<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A violation's fine is either unpaid or paid. Marking it paid keeps the
     * original fine on record but takes it off the driver's outstanding balance.
     */
    public function up()
    {
        Schema::table('violation_logs', function (Blueprint $table) {
            $table->string('payment_status', 10)->default('unpaid')->after('violation_fine');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->index('payment_status');
        });
    }

    public function down()
    {
        Schema::table('violation_logs', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['payment_status', 'paid_at']);
        });
    }
};
