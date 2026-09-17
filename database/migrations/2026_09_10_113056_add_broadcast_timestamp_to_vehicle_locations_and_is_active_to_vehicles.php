<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('vehicle_locations', 'broadcast_timestamp')) {
            Schema::table('vehicle_locations', function (Blueprint $table) {
                $table->unsignedBigInteger('broadcast_timestamp')->nullable()->after('accuracy');
            });
        }
        if (! Schema::hasColumn('vehicles', 'is_active')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('vehicle_locations', function (Blueprint $table) {
            $table->dropColumn('broadcast_timestamp');
        });
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
