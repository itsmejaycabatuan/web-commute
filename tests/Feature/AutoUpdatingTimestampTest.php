<?php

namespace Tests\Feature;

use App\Models\DriverVacancy;
use App\Models\QrCode;
use App\Models\QrScan;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * MySQL/MariaDB refresh the first NOT NULL timestamp column of a table on every update unless told not to.
 * The columns below are set by the application, so an unrelated update must leave them alone.
 */
class AutoUpdatingTimestampTest extends TestCase
{
    use DatabaseTransactions;

    public function test_a_qr_scan_keeps_the_time_it_was_scanned_when_it_is_updated(): void
    {
        Role::firstOrCreate(['name' => 'commuter']);
        $user = User::factory()->create()->assignRole('commuter');
        $scannedAt = now()->subMinutes(10)->startOfSecond();
        $scan = QrScan::create(['user_id' => $user->id, 'qr_code_id' => QrCode::create([])->id, 'scanned_at' => $scannedAt]);

        $scan->update(['matched_distance_m' => 40, 'located_at' => now()]);

        $this->assertSame($scannedAt->timestamp, $scan->fresh()->scanned_at->timestamp, 'locating must not renew the 15-minute window');
    }

    public function test_a_vacancy_keeps_the_time_it_was_opened_when_it_is_retried(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $openedAt = now()->subHours(5)->startOfSecond();
        $vacancy = DriverVacancy::create(['vehicle_id' => $vehicle->id, 'reason' => 'suspended', 'opened_at' => $openedAt]);

        $vacancy->update(['last_notified_at' => now()]);
        $vacancy->update(['resolved_at' => now(), 'resolution' => 'not_needed']);

        $this->assertSame($openedAt->timestamp, $vacancy->fresh()->opened_at->timestamp);
    }
}
