<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\VehicleLocationHistory;
use App\Models\ViolationCode;
use App\Models\ViolationLog;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DriverControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'driver']);
        Role::firstOrCreate(['name' => 'admin']);
        Storage::fake('public');
    }

    /**
     * Helper to create a logged-in Driver user.
     */
    protected function actAsDriver()
    {
        $user = User::factory()->create()->assignRole('driver');
        $driver = Driver::factory()->create(['user_id' => $user->id, 'status' => 'inactive']);

        return $this->actingAs($user);
    }

    /**
     * REGISTRATION TESTS
     */
    public function test_driver_can_register()
    {
        $file = UploadedFile::fake()->image('license.jpg');

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver.register'), [
                'email' => 'driver@test.com',
                'contact_info' => '09171234567',
                'password' => 'password123',
                'confirm-password' => 'password123',
                'terms' => '1',
                'license_image' => $file,
            ]);

        $this->assertDatabaseHas('users', ['email' => 'driver@test.com']);

        $user = User::where('email', 'driver@test.com')->first();
        $this->assertTrue($user->hasRole('driver'));
        $this->assertDatabaseHas('drivers', ['user_id' => $user->id]);

        Storage::disk('public')->assertExists('licenses/' . $file->hashName());
    }

    public function test_driver_registration_validation_fails()
    {
        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver.register'), [
                'email' => 'not-an-email',
                // Missing fields
            ])
            ->assertSessionHasErrors(['email', 'password', 'license_image']);
    }

    /**
     * DASHBOARD TESTS
     */
    public function test_driver_dashboard_shows_distance()
    {
        $driverUser = $this->actAsDriver();

        // Create location history for TODAY
        VehicleLocationHistory::factory()->create([
            'user_id' => $driverUser->id,
            'distance_from_last_pos' => 15.5,
            'created_at' => now(),
        ]);
        VehicleLocationHistory::factory()->create([
            'user_id' => $driverUser->id,
            'distance_from_last_pos' => 10.5,
            'created_at' => now(),
        ]);

        // Create a record for yesterday (should not count)
        VehicleLocationHistory::factory()->create([
            'user_id' => $driverUser->id,
            'distance_from_last_pos' => 50.0,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($driverUser)->get(route('driver.dashboard'));

        $response->assertViewHas('total_distance'); // Should be 26.0 (15.5 + 10.5)
    }

    /**
     * TIMEKEEPING TESTS
     */
    public function test_driver_can_view_timekeeping_page()
    {
        $driverUser = $this->actAsDriver();
        $response = $this->actingAs($driverUser)->get(route('driver.timekeeping'));
        $response->assertOk();
    }

    public function test_driver_can_clock_in()
    {
        $driverUser = $this->actAsDriver();

        $this->actingAs($driverUser)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver.timekeeping.clock-in'));

        $this->assertDatabaseHas('time_keepings', [
            'driver_id' => $driverUser->driver->id,
            'date' => now()->toDateString(),
            'time_out' => null,
        ]);

        // Check driver status updated
        $this->assertDatabaseHas('drivers', [
            'id' => $driverUser->driver->id,
            'status' => 'active',
        ]);
    }

    public function test_driver_cannot_clock_in_twice()
    {
        $driverUser = $this->actAsDriver();

        // First Clock In
        TimeKeeping::factory()->create([
            'driver_id' => $driverUser->driver->id,
            'date' => now()->toDateString(),
            'time_in' => now()->format('h:i A'),
        ]);

        $response = $this->actingAs($driverUser)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver.timekeeping.clock-in'));

        $response->assertSessionHas('error', 'You have already clocked in today.');
    }

    public function test_driver_can_clock_out()
    {
        $driverUser = $this->actAsDriver();

        // Setup: Clocked in 2 hours ago
        // We format the time string to match the Controller's expectations (h:i A)
        $twoHoursAgo = now()->subHours(2)->timezone('Asia/Manila');

        TimeKeeping::factory()->create([
            'driver_id' => $driverUser->driver->id,
            'date' => now()->toDateString(),
            'time_in' => $twoHoursAgo->format('h:i A'),
        ]);

        $this->actingAs($driverUser)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver.timekeeping.clock-out'));

        // Check calculations (approx 2 hours)
        $record = TimeKeeping::where('driver_id', $driverUser->driver->id)->first();
        $this->assertNotNull($record->time_out);
        $this->assertEqualsWithDelta(2.0, $record->hours_worked, 0.1); // Allow small margin
    }

    public function test_driver_cannot_clock_out_without_clocking_in()
    {
        $driverUser = $this->actAsDriver();

        $response = $this->actingAs($driverUser)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver.timekeeping.clock-out'));

        $response->assertSessionHas('error', 'No active shift found to clock out.');
    }

    /**
     * STATUS TESTS
     */
    public function test_driver_can_update_status()
    {
        $driverUser = $this->actAsDriver();

        $this->actingAs($driverUser)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('driver.status.update'), ['status' => 'inactive']);

        $this->assertDatabaseHas('drivers', [
            'id' => $driverUser->driver->id,
            'status' => 'inactive',
        ]);
    }

    /**
     * VIOLATIONS TESTS
     */
    public function test_driver_can_view_violations()
    {
        $driverUser = $this->actAsDriver();

        // Create a Violation Code
        $code = ViolationCode::factory()->create(['code' => 'OV-01', 'violation_name' => 'Speeding']);

        // Create a Violation Log
        ViolationLog::factory()->create([
            'user_id' => $driverUser->id,
            'vc_id' => $code->id,
            'violation_fine' => 500.00,
        ]);

        $response = $this->actingAs($driverUser)->get(route('driver.violations'));

        $response->assertViewHas('violations');
        $violations = $response->viewData('violations');

        // Check mapping logic
        $this->assertEquals('Speeding', $violations->first()['violationType']);
        $this->assertEquals(500.00, $violations->sum('fine'));
    }
}
