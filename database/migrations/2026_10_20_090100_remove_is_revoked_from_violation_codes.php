<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Violation codes no longer carry a "license revocation" flag.
     */
    public function up()
    {
        if (Schema::hasColumn('violation_codes', 'is_revoked')) {
            Schema::table('violation_codes', function (Blueprint $table) {
                $table->dropColumn('is_revoked');
            });
        }
    }

    public function down()
    {
        Schema::table('violation_codes', function (Blueprint $table) {
            $table->boolean('is_revoked')->default(false);
        });
    }
};
