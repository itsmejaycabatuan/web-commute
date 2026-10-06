<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * UCN_SC_E012 (Manage Drivers) E2 — a license number may only be held by one
     * driver, and UCN_SC_E018 (Manage Commuters) A4 — a commuter account can be
     * suspended by an admin, which locks it out of signing in while keeping its
     * wallet and receipts auditable.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_suspended')->default(false)->after('password');
            $table->timestamp('suspended_at')->nullable()->after('is_suspended');

            $table->index('is_suspended');
        });

        // Backstop for the controller-level `unique` rule. Nullable columns are
        // excluded from MySQL unique indexes, so drivers without a license
        // number are unaffected.
        Schema::table('drivers', function (Blueprint $table) {
            $table->unique('license_number');
        });
    }

    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropUnique(['license_number']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_suspended']);
            $table->dropColumn(['is_suspended', 'suspended_at']);
        });
    }
};