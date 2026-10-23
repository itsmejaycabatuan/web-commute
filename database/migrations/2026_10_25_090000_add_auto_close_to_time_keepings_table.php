<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A shift the system closed because the driver never clocked out:
     *  - `auto_closed_at`: when the scheduler closed it (null = closed by the driver or the manager);
     *  - `reviewed_at`: when a Driver Manager confirmed the entry (null = still needs review).
     */
    public function up()
    {
        Schema::table('time_keepings', function (Blueprint $table) {
            $table->timestamp('auto_closed_at')->nullable()->after('overtime_hours');
            $table->timestamp('reviewed_at')->nullable()->after('auto_closed_at');
        });
    }

    public function down()
    {
        Schema::table('time_keepings', function (Blueprint $table) {
            $table->dropColumn(['auto_closed_at', 'reviewed_at']);
        });
    }
};
