<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The violation forms label remarks "Optional notes...", and the bulk form
     * submits them empty, but the column was NOT NULL - so a row without remarks
     * made the whole bulk insert fail. Remarks are genuinely optional.
     */
    public function up()
    {
        Schema::table('violation_logs', function (Blueprint $table) {
            $table->string('remarks')->nullable()->change();
        });
    }

    public function down()
    {
        DB::table('violation_logs')->whereNull('remarks')->update(['remarks' => '']);

        Schema::table('violation_logs', function (Blueprint $table) {
            $table->string('remarks')->nullable(false)->change();
        });
    }
};
