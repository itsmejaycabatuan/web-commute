<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Inactivity automation (`drivers:inactivity-check`): a driver who has not clocked in for a few
     * days is emailed and told to contact HR; one who stays away for over a week is suspended.
     *
     *  - `inactivity_warned_at`: when the warning email went out (null = not warned). Cleared as soon
     *    as the driver clocks in again, so the next lapse starts a fresh warning.
     *  - `reactivated_at`: when a suspension was lifted, so a reinstated driver gets a fresh
     *    inactivity clock instead of being suspended again on the next run.
     */
    public function up()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->timestamp('inactivity_warned_at')->nullable()->after('suspension_reason');
            $table->timestamp('reactivated_at')->nullable()->after('inactivity_warned_at');
        });
    }

    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['inactivity_warned_at', 'reactivated_at']);
        });
    }
};
