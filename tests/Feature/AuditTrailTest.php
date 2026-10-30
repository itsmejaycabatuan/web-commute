<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\FareRate;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The audit trail (activity_log): what is recorded, what must NOT be, and that every entry says
 * who did what to which record. Written for the admin audit page that reads this table.
 */
class AuditTrailTest extends TestCase
{
    use DatabaseTransactions;

    private int $baseline = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        foreach (['admin', 'driver_manager', 'maintenance_manager', 'driver', 'commuter'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // The dev database already holds real entries; only look at what THIS test writes.
        $this->baseline = (int) Activity::max('id');
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes)->assignRole($role);
        $user->markEmailAsVerified();

        return $user;
    }

    /** Entries written since the test started, optionally narrowed. */
    private function entries(?string $event = null, ?string $category = null)
    {
        return Activity::where('id', '>', $this->baseline)
            ->when($event, fn ($q) => $q->where('event', $event))
            ->when($category, fn ($q) => $q->where('log_name', $category))
            ->orderBy('id')
            ->get();
    }

    private function newEntryCount(): int
    {
        return $this->entries()->count();
    }

    // ───────────────────────── noise stays out ─────────────────────────

    public function test_map_polling_and_page_views_write_nothing()
    {
        $commuter = $this->userWithRole('commuter');

        $this->get('/track/vehicles/active')->assertOk();                       // guest poll
        $this->actingAs($commuter)->get('/track/vehicles/active')->assertOk(); // commuter poll
        $this->actingAs($commuter)->get(route('settings.edit'))->assertOk();

        $this->assertSame(0, $this->newEntryCount(), 'polling or viewing a page must not write to the audit trail');
    }

    public function test_a_vehicle_heartbeat_and_driver_availability_are_not_audited()
    {
        $driver = Driver::factory()->approved()->create();
        $vehicle = Vehicle::factory()->withDriver($driver->id)->create(['status' => 'active']);
        $before = $this->newEntryCount();

        Vehicle::where('id', $vehicle->id)->update(['is_active' => false]); // what the scheduler does
        $vehicle->update(['is_active' => true]);                             // excluded attribute
        $driver->update(['status' => 'active']);                             // availability toggle / clock-in
        $driver->update(['status' => 'inactive']);

        $this->assertSame($before, $this->newEntryCount());
    }

    // ───────────────────────── sign-in and sign-out ─────────────────────────

    public function test_a_successful_sign_in_is_audited_with_the_user_and_request_context()
    {
        $user = $this->userWithRole('commuter');

        $this->post(route('users.login'), ['email' => $user->email, 'password' => '123456789', 'remember' => 'on'])
            ->assertRedirect();

        $entry = $this->entries('login', 'auth')->first();

        $this->assertNotNull($entry, 'sign-in must be audited');
        $this->assertSame($user->id, $entry->causer_id);
        $this->assertSame($user->id, $entry->subject_id);
        $this->assertTrue($entry->properties['remember']);
        $this->assertSame($user->email, $entry->properties['context']['actor_email']);
        $this->assertSame('commuter', $entry->properties['context']['actor_role']);
        $this->assertNotEmpty($entry->properties['context']['ip']);
        $this->assertArrayHasKey('user_agent', $entry->properties['context']);
    }

    public function test_a_failed_sign_in_records_the_email_but_never_the_password()
    {
        $user = $this->userWithRole('commuter');

        $this->post(route('users.login'), ['email' => $user->email, 'password' => 'definitely-wrong-pw'])
            ->assertSessionHasErrors('credentials');

        $entry = $this->entries('login_failed', 'auth')->first();

        $this->assertNotNull($entry);
        $this->assertNull($entry->causer_id, 'nobody is signed in');
        $this->assertSame($user->email, $entry->properties['email']);
        $this->assertStringNotContainsString('definitely-wrong-pw', json_encode($entry->properties));
        $this->assertNotEmpty($entry->properties['context']['ip'], 'the IP is what makes a failed attempt traceable');
    }

    public function test_a_suspended_account_trying_to_sign_in_is_audited_as_blocked()
    {
        $user = $this->userWithRole('commuter');
        $user->suspend();

        $this->post(route('users.login'), ['email' => $user->email, 'password' => '123456789'])
            ->assertSessionHasErrors('credentials');

        $entry = $this->entries('login_blocked', 'auth')->first();

        $this->assertNotNull($entry);
        $this->assertSame($user->id, $entry->subject_id);
        $this->assertSame('suspended', $entry->properties['reason']);
        $this->assertCount(0, $this->entries('login'), 'a blocked sign-in is not also a sign-in');
    }

    public function test_signing_out_is_audited_once_with_the_user()
    {
        $user = $this->userWithRole('commuter');

        $this->actingAs($user)->post(route('users.logout'))->assertRedirect(route('login'));

        $logout = $this->entries('logout', 'auth');
        $this->assertCount(1, $logout);
        $this->assertSame($user->id, $logout->first()->causer_id);
    }

    // ───────────────────────── accounts ─────────────────────────

    public function test_registration_is_audited()
    {
        $this->post(route('users.register'), [
            'email' => 'brand.new@example.com', 'password' => 'password123',
            'confirm-password' => 'password123', 'terms' => 'on',
        ])->assertRedirect();

        $user = User::where('email', 'brand.new@example.com')->firstOrFail();
        $entry = $this->entries('registered', 'account')->first();

        $this->assertNotNull($entry);
        $this->assertSame($user->id, $entry->subject_id);
        $this->assertSame('self-registration', $entry->properties['source']);
        $this->assertCount(1, $this->entries(null, 'account'), 'creation must not be logged twice (model event is off for created)');
    }

    public function test_changing_a_password_is_audited_without_the_password()
    {
        $user = $this->userWithRole('driver');

        $this->actingAs($user)->put(route('settings.password'), [
            'current_password' => '123456789',
            'password' => 'a-new-secret-pw',
            'password_confirmation' => 'a-new-secret-pw',
        ])->assertSessionHas('success');

        $entry = $this->entries('password_changed', 'auth')->first();

        $this->assertNotNull($entry);
        $this->assertSame($user->id, $entry->causer_id);
        $this->assertCount(1, $this->entries(), 'the password column is not audited by the model, only by this one entry');
        $this->assertStringNotContainsString('a-new-secret-pw', json_encode($entry->properties));
        $this->assertStringNotContainsString('$2y$', json_encode($entry->properties));
    }

    public function test_changing_your_own_email_records_old_and_new_values()
    {
        $user = $this->userWithRole('commuter', ['email' => 'before@example.com']);

        $this->actingAs($user)->put(route('settings.update'), ['email' => 'after@example.com'])
            ->assertSessionHas('success');

        $entry = $this->entries('updated', 'account')->first();

        $this->assertNotNull($entry);
        $this->assertSame('before@example.com', $entry->properties['old']['email']);
        $this->assertSame('after@example.com', $entry->properties['attributes']['email']);
        $this->assertArrayNotHasKey('password', $entry->properties['attributes']);
    }

    public function test_deleting_your_own_account_leaves_exactly_one_entry_with_the_email()
    {
        $user = $this->userWithRole('commuter', ['email' => 'goodbye@example.com']);
        $id = $user->id;

        $this->actingAs($user)->delete(route('users.delete-account'))->assertRedirect(route('login'));

        $this->assertNull(User::find($id));

        $entries = $this->entries(null, 'account');
        $this->assertCount(1, $entries, 'self-deletion is one entry, not one per layer');
        $this->assertSame('deleted_self', $entries->first()->event);
        $this->assertSame('goodbye@example.com', $entries->first()->properties['email']);
        $this->assertSame($id, $entries->first()->causer_id, 'it still knows who did it');
    }

    public function test_the_staff_lifecycle_is_audited_by_the_admin_who_did_it()
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('staff.store'), [
            'email' => 'mgr@example.com', 'role' => 'driver_manager',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHas('success');
        $staff = User::where('email', 'mgr@example.com')->firstOrFail();

        $this->actingAs($admin)->put(route('staff.update', $staff), [
            'email' => 'mgr@example.com', 'role' => 'maintenance_manager', 'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertSessionHas('success');

        $this->actingAs($admin)->patch(route('staff.suspension', $staff));
        $this->actingAs($admin)->patch(route('staff.suspension', $staff));
        $this->actingAs($admin)->delete(route('staff.destroy', $staff))->assertSessionHas('success');

        $events = $this->entries()->pluck('event')->all();
        $this->assertSame(['created', 'role_changed', 'password_set_by_admin', 'suspended', 'reactivated', 'deleted'], $events);

        foreach ($this->entries() as $entry) {
            $this->assertSame($admin->id, $entry->causer_id, "{$entry->event} must name the admin");
            $this->assertSame($staff->id, $entry->subject_id);
        }

        $role = $this->entries('role_changed')->first();
        $this->assertSame('driver_manager', $role->properties['old']['role']);
        $this->assertSame('maintenance_manager', $role->properties['attributes']['role']);
        $this->assertSame('maintenance_manager', $this->entries('deleted')->first()->properties['role']);
    }

    public function test_suspending_and_reactivating_a_commuter_is_audited_once_each()
    {
        $admin = $this->userWithRole('admin');
        $commuter = $this->userWithRole('commuter');

        $this->actingAs($admin)->patch(route('commuters.suspension', $commuter));
        $this->actingAs($admin)->patch(route('commuters.suspension', $commuter));

        $this->assertSame(['suspended', 'reactivated'], $this->entries()->pluck('event')->all());
    }

    // ───────────────────────── records ─────────────────────────

    public function test_vehicle_changes_record_who_which_record_and_old_and_new_values()
    {
        $manager = $this->userWithRole('maintenance_manager');
        $plate = 'AUD-'.random_int(1000, 9999);

        $this->actingAs($manager)->postJson(route('vehicles.store'), [
            'year' => 2022, 'brand' => 'Toyota', 'model' => 'Hiace', 'plate_number' => $plate,
            'status' => 'active', 'acquisition_date' => '2022-01-01',
        ])->assertCreated();
        $vehicle = Vehicle::where('plate_number', $plate)->firstOrFail();

        $this->actingAs($manager)->patchJson(route('vehicles.update', $vehicle), [
            'year' => 2022, 'brand' => 'Toyota', 'model' => 'Hiace', 'plate_number' => $plate,
            'status' => 'maintenance', 'acquisition_date' => '2022-01-01',
        ])->assertOk();

        $this->actingAs($manager)->deleteJson(route('vehicles.destroy', $vehicle))->assertOk();

        $entries = $this->entries(null, 'fleet');
        $this->assertSame(['created', 'updated', 'deleted'], $entries->pluck('event')->all());

        [$created, $updated, $deleted] = $entries->all();

        $this->assertSame($manager->id, $created->causer_id);
        $this->assertSame(Vehicle::class, $created->subject_type);
        $this->assertSame($vehicle->id, (int) $created->subject_id);
        $this->assertSame($plate, $created->properties['attributes']['plate_number']);
        $this->assertSame("Created vehicle {$plate}", $created->description);

        $this->assertSame('active', $updated->properties['old']['status']);
        $this->assertSame('maintenance', $updated->properties['attributes']['status']);
        $this->assertSame(['status'], array_keys($updated->properties['attributes']), 'only what changed is stored');

        $this->assertSame($plate, $deleted->properties['old']['plate_number']);
        $this->assertSame('maintenance_manager', $created->properties['context']['actor_role']);
    }

    public function test_driver_approval_and_suspension_are_audited_with_their_reasons()
    {
        $admin = $this->userWithRole('admin');
        $pending = Driver::factory()->pending()->create();

        $this->actingAs($admin)->post(route('drivers.approve', ['user' => $pending->id]), [
            'license_number' => 'AUD-LIC-1', 'license_code' => 'PRO', 'expiration_date' => '2031-01-01',
        ])->assertSessionHas('success');

        $approval = $this->entries('updated', 'driver')->first();
        $this->assertNotNull($approval);
        $this->assertStringStartsWith('Approved driver', $approval->description);
        $this->assertSame($admin->id, $approval->causer_id);
        $this->assertEquals(0, $approval->properties['old']['is_approved']);
        $this->assertEquals(1, $approval->properties['attributes']['is_approved']);

        $driver = Driver::factory()->approved()->create();
        $vehicle = Vehicle::factory()->withDriver($driver->id)->create(['status' => 'active']);

        $this->actingAs($admin)->patch(route('drivers.suspension', ['driver' => $driver->id]), ['suspension_reason' => 'Unsafe driving']);
        $this->actingAs($admin)->patch(route('drivers.suspension', ['driver' => $driver->id]));

        $suspended = $this->entries('suspended', 'driver')->first();
        $this->assertSame('Unsafe driving', $suspended->properties['reason']);
        $this->assertTrue($suspended->properties['vehicle_released']);
        $this->assertCount(1, $this->entries('reactivated', 'driver'));
    }

    public function test_a_fare_rate_edit_is_audited_as_one_entry_with_old_and_new_values()
    {
        $admin = $this->userWithRole('admin');
        $rate = FareRate::factory()->create(['regular' => 20]);
        $before = $this->entries('fare_rates_updated')->count();

        $this->actingAs($admin)->put(route('fares.bulk-update'), [
            'rates' => [$rate->id => ['id' => $rate->id, 'regular' => 25.5]],
        ])->assertSessionHas('success');

        $entries = $this->entries('fare_rates_updated', 'fare');
        $this->assertCount($before + 1, $entries);

        $change = $entries->last()->properties['changes'][0];
        $this->assertEquals(20, $change['regular']['old']);
        $this->assertEquals(25.5, $change['regular']['new']);
        $this->assertSame($admin->id, $entries->last()->causer_id);
    }

    public function test_saving_fare_rates_without_changing_anything_writes_nothing()
    {
        $admin = $this->userWithRole('admin');
        $rate = FareRate::factory()->create(['regular' => 20]);
        $before = $this->entries('fare_rates_updated')->count();

        $this->actingAs($admin)->put(route('fares.bulk-update'), [
            'rates' => [$rate->id => ['id' => $rate->id, 'regular' => 20]],
        ])->assertSessionHas('success');

        $this->assertCount($before, $this->entries('fare_rates_updated'));
    }

    // ───────────────────────── guarantees ─────────────────────────

    public function test_no_secret_ever_reaches_the_audit_trail()
    {
        $admin = $this->userWithRole('admin');
        $secret = 'Sup3r-S3cret-Pw!';

        $this->actingAs($admin)->post(route('staff.store'), [
            'email' => 'secret.mgr@example.com', 'role' => 'driver_manager',
            'password' => $secret, 'password_confirmation' => $secret,
        ])->assertSessionHas('success');
        $staff = User::where('email', 'secret.mgr@example.com')->firstOrFail();

        $this->actingAs($staff)->put(route('settings.password'), [
            'current_password' => $secret, 'password' => $secret.'2', 'password_confirmation' => $secret.'2',
        ]);

        $driver = Driver::factory()->approved()->create(['license_image_data' => 'BASE64-LICENCE-BLOB']);
        $driver->update(['license_image_data' => 'BASE64-CHANGED', 'name' => 'Renamed Driver']);

        $dump = json_encode($this->entries()->map(fn ($e) => [$e->description, $e->properties])->all());

        foreach ([$secret, '$2y$', 'BASE64-LICENCE-BLOB', 'BASE64-CHANGED', 'remember_token'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $dump, "'{$forbidden}' must never be stored in the audit trail");
        }
        $this->assertStringContainsString('Renamed Driver', $dump, 'ordinary changes are still recorded');
    }

    public function test_every_entry_made_over_http_names_the_actor_and_carries_context()
    {
        $admin = $this->userWithRole('admin');
        $commuter = $this->userWithRole('commuter');

        $this->actingAs($admin)->patch(route('commuters.suspension', $commuter));

        $entry = $this->entries()->first();
        $this->assertSame($admin->email, $entry->properties['context']['actor_email']);
        $this->assertSame('admin', $entry->properties['context']['actor_role']);
        $this->assertNotEmpty($entry->properties['context']['ip']);
    }

    public function test_an_audit_failure_never_breaks_the_request()
    {
        $admin = $this->userWithRole('admin');
        $commuter = $this->userWithRole('commuter');

        // Make every save of an activity blow up, the way a full disk or a dropped table would.
        Activity::creating(fn () => throw new \RuntimeException('audit store unavailable'));

        $this->actingAs($admin)->patch(route('commuters.suspension', $commuter))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($commuter->fresh()->isSuspended(), 'the action itself must still happen');

    }

    // ───────────────────────── housekeeping ─────────────────────────

    public function test_the_purge_command_removes_only_legacy_noise()
    {
        activity()->event('Index')->log('Action performed: index');
        activity()->event('Getactivevehicles')->log('Action performed: getActiveVehicles');
        $real = activity('auth')->event('login')->log('Signed in someone@example.com');

        $this->artisan('activitylog:purge-noise', ['--dry-run' => true])->assertExitCode(0);
        $this->assertTrue(Activity::where('description', 'like', 'Action performed:%')->exists(), 'dry run deletes nothing');

        $this->artisan('activitylog:purge-noise', ['--force' => true])->assertExitCode(0);

        $this->assertFalse(Activity::where('description', 'like', 'Action performed:%')->exists());
        $this->assertNotNull(Activity::find($real->id), 'meaningful entries are untouched');
    }
}
