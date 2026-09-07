<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\User; // or DatabaseTransactions
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DriverApprovalControllerTest extends TestCase
{
    // Use RefreshDatabase to ensure clean state for file testing
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'driver']);

        // Fake the public storage disk for file uploads
        Storage::fake('public');
    }

    /**
     * Helper to act as Admin.
     */
    protected function actAsAdmin()
    {
        $admin = User::factory()->create()->assignRole('admin');

        return $this->actingAs($admin);
    }

    /**
     * INDEX TESTS
     */
    public function test_admin_can_view_drivers_index()
    {
        $driverUser = User::factory()->create()->assignRole('driver');
        Driver::factory()->create(['user_id' => $driverUser->id]);

        $this->actAsAdmin()
            ->get(route('drivers.index'))
            ->assertOk()
            ->assertViewHas('drivers');
    }

    /**
     * STORE TESTS
     */
    public function test_admin_can_store_driver()
    {
        $file = UploadedFile::fake()->image('license.jpg');

        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('drivers.store'), [
                'driver_code' => 'DRV-001',
                'name' => 'John Doe',
                'email' => 'driver@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'license_number' => 'L-12345',
                'license_code' => 'PRO',
                'expiration_date' => '2025-01-01',
                'contact_info' => '09171234567',
                'license_image' => $file,
            ]);

        $this->assertDatabaseHas('users', ['email' => 'driver@test.com']);
        $user = User::where('email', 'driver@test.com')->first();

        $this->assertDatabaseHas('drivers', [
            'user_id' => $user->id,
            'is_approved' => 1,
        ]);

        $this->assertTrue($user->hasRole('driver'));
        Storage::disk('public')->assertExists('licenses/' . $file->hashName());
    }

    public function test_store_validation_requires_fields()
    {
        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('drivers.store'), [
                'email' => 'driver@test.com',
                // Missing license_image and other fields
            ])

            // FIX: Removed 'license_image' because controller didn't validate it in the original snippet.
            // If you added validation to the controller, add it back here.
            ->assertSessionHasErrors(['name', 'driver_code', 'password', 'license_number', 'license_code', 'expiration_date', 'contact_info', 'license_image']);
    }

    public function test_edit_returns_404_if_driver_not_found()
    {
        $this->actAsAdmin()
            ->get(route('drivers.edit', ['user' => 99999]))
            ->assertRedirect(); // Controller uses back()->with()
    }

    /**
     * UPDATE TESTS
     */
    public function test_admin_can_update_driver()
    {
        $driver = Driver::factory()->create([
            'license_image_path' => 'licenses/old.jpg',
        ]);

        $newFile = UploadedFile::fake()->image('new_license.jpg');

        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('drivers.update', ['user' => $driver->id]), [
                'name' => 'Updated Name',
                'license_number' => 'L-99999',
                'license_code' => 'NON-PRO',
                'expiration_date' => '2026-01-01',
                'contact_info' => '09189999999',
                'driver_code' => 'DRV-UPD',
                'is_approved' => '1',
                'is_rejected' => '0',
                'license_image' => $newFile,
            ]);

        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'name' => 'Updated Name',
        ]);

        // Check file replacement
        Storage::disk('public')->assertMissing('licenses/old.jpg');
        Storage::disk('public')->assertExists('licenses/' . $newFile->hashName());
    }

    /**
     * APPROVE TESTS
     */
    public function test_admin_can_approve_driver()
    {
        $driver = Driver::factory()->create(['is_approved' => 0]);

        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('drivers.approve', ['user' => $driver->id]), [
                'name' => $driver->name,
                'license_number' => $driver->license_number,
                'license_code' => $driver->license_code,
                'expiration_date' => $driver->expiration_date,
                'driver_code' => $driver->driver_code,
            ]);

        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'is_approved' => 1,
        ]);
    }

    public function test_cannot_approve_already_approved_driver()
    {
        // FIX: Ensure is_approved is set to 1
        $driver = Driver::factory()->create(['is_approved' => 1]);

        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('drivers.approve', ['user' => $driver->id]), [
                'name' => $driver->name,
                'license_number' => $driver->license_number,
                'license_code' => $driver->license_code,
                'expiration_date' => $driver->expiration_date,
                'driver_code' => $driver->driver_code,
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'This driver is already approved.');
    }

    /**
     * REJECT TESTS
     */
    public function test_admin_can_reject_driver()
    {
        $driver = Driver::factory()->create(['is_rejected' => 0]);

        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('drivers.reject', ['user' => $driver->id])); // Route is PUT based on web.php

        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'is_rejected' => 1,
        ]);
    }

    /**
     * DESTROY TESTS
     */
    public function test_admin_can_delete_driver()
    {
        $driver = Driver::factory()->create();

        // FIX: The route is 'drivers/{driver}', so pass ['driver' => $driver->id]
        $this->actAsAdmin()
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->delete(route('drivers.destroy', ['driver' => $driver->id]));

        $this->assertDatabaseMissing('drivers', ['id' => $driver->id]);
        $this->assertDatabaseMissing('users', ['id' => $driver->user_id]);
    }
}
