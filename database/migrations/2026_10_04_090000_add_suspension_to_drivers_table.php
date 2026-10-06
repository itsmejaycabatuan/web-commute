<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * UCN_SC_E012 (Manage Drivers) — a driver account can be suspended /
     * reactivated by an admin or driver manager, the same way a commuter account
     * can (UCN_SC_E018 A4).
     *
     * Suspension is a flag rather than a delete so the driver's licence,
     * timekeeping history and violation record stay auditable.
     */
    public function up()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->boolean('is_suspended')->default(false)->after('is_rejected');
            $table->timestamp('suspended_at')->nullable()->after('is_suspended');
            $table->string('suspension_reason')->nullable()->after('suspended_at');

            $table->index('is_suspended');
        });
    }

    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropIndex(['is_suspended']);
            $table->dropColumn(['is_suspended', 'suspended_at', 'suspension_reason']);
        });
    }
};