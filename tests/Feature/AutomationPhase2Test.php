<?php

namespace Tests\Feature;

use App\Events\LocationUpdated;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\Fare;
use App\Models\FareRate;
use App\Models\Payment;
use App\Models\QrCode;
use App\Models\QrScan;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Models\VehicleLocationHistory;
use App\Models\ViolationCode;
use App\Models\ViolationLog;
use App\Models\Wallet;
use App\Notifications\AppNotification;
use App\Notifications\DriverApplicationDecided;
use App\Notifications\DriverApplicationReceived;
use App\Notifications\FareEvasionFlag;
use App\Notifications\GatewayAlert;
use App\Notifications\PaymentFailed;
use App\Notifications\PaymentReceipt;
use App\Notifications\RepeatViolatorFlag;
use App\Notifications\ScheduledReport;
use App\Notifications\TopupReceipt;
use App\Notifications\ViolationPaid;
use App\Notifications\ViolationRecorded;
use App\Services\FleetAnalyticsService;
use App\Services\GatewayMonitor;
use App\Services\NearbyVehicleFinder;
use App\Services\PaymentSettlement;
use App\Services\PeriodReportService;
use App\Services\PayMongoService;
use App\Support\Notifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The second round of automation: notifications, payment reconciliation and receipts, QR location checks,
 * fleet analytics with its flags, and the scheduled reports.
 */
class AutomationPhase2Test extends TestCase
{
    use DatabaseTransactions;

    private const NEAR = [10.3157, 123.8854];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'commuter', 'driver', 'driver_manager', 'maintenance_manager'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->withoutMiddleware(VerifyCsrfToken::class);
        app(GatewayMonitor::class)->reset();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        PayMongoService::flushConfigCache();
        parent::tearDown();
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function staff(string $role): User
    {
        return User::factory()->create(['email_verified_at' => now()])->assignRole($role);
    }

    private function commuter(float $balance = 500): User
    {
        $user = $this->staff('commuter');
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => (string) $balance]);

        return $user->fresh();
    }

    /** @return array{0: User, 1: Driver} */
    private function driver(array $state = []): array
    {
        $user = User::factory()->create(['email_verified_at' => now()])->assignRole('driver');
        $driver = Driver::factory()->approved()->create(array_merge(['user_id' => $user->id, 'status' => 'active', 'is_suspended' => false], $state));

        return [$user->fresh(), $driver];
    }

    private function code(): ViolationCode
    {
        return ViolationCode::create(['code' => 'T'.uniqid(), 'violation_name' => 'Test violation', 'first_offense' => 100, 'second_offense' => 200, 'third_offense' => 300, 'fourth_offense' => 400]);
    }

    private function payment(User $user, array $attrs = []): Payment
    {
        return Payment::create(array_merge([
            'paid_by' => $user->id, 'starting_point' => 'A', 'destination' => 'B', 'total_distance' => 5,
            'payment_method' => 'GCash', 'transaction_id' => '#SC-'.uniqid(), 'price' => 18, 'status' => 'pending',
        ], $attrs));
    }

    private function enableGateway(): void
    {
        config([
            'paymongo.enabled' => true, 'paymongo.secret_key' => 'sk_test_fake_key', 'paymongo.public_key' => 'pk_test_fake',
            'paymongo.base_url' => 'https://api.paymongo.com/v1',
        ]);
        PayMongoService::flushConfigCache();
    }

    private function intent(string $id, string $status)
    {
        return Http::response(['data' => ['id' => $id, 'attributes' => ['status' => $status]]]);
    }

    // ── Notifications ────────────────────────────────────────────────────

    public function test_notifier_never_lets_a_failing_send_break_the_caller(): void
    {
        $user = $this->staff('commuter');

        $explodes = new class extends AppNotification {
            public function toMail($notifiable): MailMessage
            {
                throw new \RuntimeException('mail server down');
            }
        };

        $this->assertSame(0, Notifier::send($user, $explodes));
        $this->assertSame(0, Notifier::send(null, $explodes));
    }

    public function test_notifier_skips_accounts_with_no_email_and_suspended_staff(): void
    {
        Notification::fake();
        $active = $this->staff('admin');
        $suspended = $this->staff('admin');
        $suspended->forceFill(['is_suspended' => true])->save();

        Notifier::toRole('admin', new GatewayAlert(3, 15, 'x'));

        Notification::assertSentTo($active, GatewayAlert::class);
        Notification::assertNotSentTo($suspended, GatewayAlert::class);
    }

    public function test_channels_come_from_one_config_value(): void
    {
        config(['alerts.channels' => ['mail', 'database']]);

        $this->assertSame(['mail', 'database'], (new GatewayAlert(3, 15, 'x'))->via(new User));
    }

    public function test_driver_registration_notifies_admins_and_driver_managers(): void
    {
        Notification::fake();
        Storage::fake('public');
        $admin = $this->staff('admin');
        $manager = $this->staff('driver_manager');
        $other = $this->staff('maintenance_manager');

        $this->post(route('driver.register'), [
            'name' => 'Pedro Penduko', 'email' => 'pedro@example.com', 'contact_info' => '09171234567',
            'password' => 'password123', 'confirm-password' => 'password123', 'terms' => '1',
            'license_image' => UploadedFile::fake()->image('license.jpg'),
        ])->assertRedirect(route('login'));

        Notification::assertSentTo($admin, DriverApplicationReceived::class, fn ($n) => $n->email === 'pedro@example.com');
        Notification::assertSentTo($manager, DriverApplicationReceived::class);
        Notification::assertNotSentTo($other, DriverApplicationReceived::class);
    }

    public function test_driver_hears_about_approval_and_rejection(): void
    {
        Notification::fake();
        $admin = $this->staff('admin');

        $user = User::factory()->create(['email_verified_at' => now()])->assignRole('driver');
        $driver = Driver::factory()->pending()->create(['user_id' => $user->id]);

        $this->actingAs($admin)->post(route('drivers.approve', ['user' => $driver->id]), [
            'license_number' => 'N01-99-123456', 'license_code' => 'PRO', 'expiration_date' => now()->addYear()->toDateString(),
        ])->assertSessionHas('success');

        Notification::assertSentTo($user, DriverApplicationDecided::class, fn ($n) => $n->approved === true);

        $user2 = User::factory()->create(['email_verified_at' => now()])->assignRole('driver');
        $driver2 = Driver::factory()->pending()->create(['user_id' => $user2->id]);

        $this->actingAs($admin)->post(route('drivers.reject', ['user' => $driver2->id]))->assertSessionHas('success');

        Notification::assertSentTo($user2, DriverApplicationDecided::class, fn ($n) => $n->approved === false);
    }

    public function test_driver_is_told_about_a_new_violation_and_about_a_paid_fine(): void
    {
        Notification::fake();
        $manager = $this->staff('driver_manager');
        [$user] = $this->driver();
        $code = $this->code();

        $this->actingAs($manager)->post(route('driver-manager.violations-log.store'), [
            'user_id' => $user->id, 'vc_id' => $code->id, 'violation_instance' => 1, 'violation_fine' => 100,
            'place_of_violation' => 'Colon', 'date_of_violation' => today()->toDateString(), 'time_of_violation' => '10:00',
        ])->assertSessionHas('success');

        Notification::assertSentTo($user, ViolationRecorded::class, fn ($n) => $n->violation->violation_fine == 100);

        $log = ViolationLog::where('user_id', $user->id)->latest('id')->first();

        $this->actingAs($manager)->patch(route('driver-manager.violations-log.payment', $log), ['payment_status' => 'paid']);
        Notification::assertSentToTimes($user, ViolationPaid::class, 1);

        $this->actingAs($manager)->patch(route('driver-manager.violations-log.payment', $log), ['payment_status' => 'unpaid']);
        Notification::assertSentToTimes($user, ViolationPaid::class, 1);
    }

    public function test_bulk_violations_send_one_email_each(): void
    {
        Notification::fake();
        $manager = $this->staff('driver_manager');
        [$user] = $this->driver();
        $code = $this->code();
        $row = ['vc_id' => $code->id, 'violation_instance' => 1, 'violation_fine' => 100, 'place_of_violation' => 'Colon', 'date_of_violation' => today()->toDateString(), 'time_of_violation' => '10:00'];

        $this->actingAs($manager)->post(route('driver-manager.violations-log.store-bulk'), [
            'user_id' => $user->id, 'violations' => [$row, array_merge($row, ['violation_instance' => 2, 'violation_fine' => 200])],
        ])->assertSessionHas('success');

        Notification::assertSentToTimes($user, ViolationRecorded::class, 2);
    }

    public function test_violation_email_reads_well(): void
    {
        [$user] = $this->driver();
        $code = $this->code();
        $log = ViolationLog::create([
            'user_id' => $user->id, 'vc_id' => $code->id, 'violation_instance' => '2', 'violation_fine' => 200,
            'date_of_violation' => '2026-10-20', 'time_of_violation' => '17:10:00', 'place_of_violation' => 'Colon Street',
        ]);

        $mail = (new ViolationRecorded($log))->toMail($user);
        $text = implode("\n", $mail->introLines);

        $this->assertStringContainsString('2nd', $text);
        $this->assertStringContainsString('₱200.00', $text);
        $this->assertStringContainsString('Colon Street', $text);
        $this->assertStringContainsString($user->driver->name, $mail->greeting);
    }

    public function test_topup_emails_a_receipt_with_the_new_balance(): void
    {
        Notification::fake();
        $user = $this->commuter(50);

        $this->actingAs($user)->post(route('payment.topup.process'), ['amount' => 100, 'payment-method' => 'gcash'])
            ->assertSessionHas('success');

        Notification::assertSentTo($user, TopupReceipt::class, fn ($n) => abs($n->newBalance - 150) < 0.001 && $n->topup->amount_added == 100);
    }

    // ── Payment settlement & reconciliation ──────────────────────────────

    public function test_settling_a_payment_emails_one_receipt_however_many_times_it_is_told(): void
    {
        Notification::fake();
        $user = $this->commuter();
        $payment = $this->payment($user);
        $settlement = app(PaymentSettlement::class);

        $this->assertTrue($settlement->settle($payment));
        $this->assertFalse($settlement->settle($payment), 'a second webhook or poll finds it already done');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
        Notification::assertSentToTimes($user, PaymentReceipt::class, 1);
    }

    public function test_a_failed_payment_tells_the_commuter_and_never_overwrites_a_paid_one(): void
    {
        Notification::fake();
        $user = $this->commuter();
        $settlement = app(PaymentSettlement::class);

        $failing = $this->payment($user);
        $this->assertTrue($settlement->fail($failing, 'Declined.'));
        $this->assertSame('failed', $failing->fresh()->status);
        Notification::assertSentTo($user, PaymentFailed::class);

        $paid = $this->payment($user, ['status' => 'paid', 'paid_at' => now()]);
        $this->assertFalse($settlement->fail($paid, 'Late decline.'));
        $this->assertSame('paid', $paid->fresh()->status);
    }

    public function test_wallet_payment_emails_a_receipt(): void
    {
        Notification::fake();
        $fare = Fare::factory()->create();
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 1, 'regular' => 15, 'discount' => 12]);
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 5, 'regular' => 17.25, 'discount' => 13.75]);
        $user = $this->commuter();

        $this->actingAs($user)->post(route('payment.process'), [
            'pickup' => 'A', 'destination' => 'B', 'distance' => '5.2', 'amount' => 18,
            'payment-method' => 'Wallet', 'transaction-id' => '#SC-RCPT01',
        ])->assertSessionHas('success');

        Notification::assertSentTo($user, PaymentReceipt::class, fn ($n) => $n->payment->transaction_id === '#SC-RCPT01');
    }

    public function test_reconcile_settles_fails_and_leaves_alone_as_the_gateway_says(): void
    {
        Notification::fake();
        $this->enableGateway();
        Http::fake([
            'api.paymongo.com/v1/payment_intents/pi_paid' => $this->intent('pi_paid', 'succeeded'),
            'api.paymongo.com/v1/payment_intents/pi_failed' => $this->intent('pi_failed', 'failed'),
            'api.paymongo.com/v1/payment_intents/pi_waiting' => $this->intent('pi_waiting', 'awaiting_payment_method'),
        ]);
        $user = $this->commuter();
        $old = now()->subMinutes(20);
        $paid = $this->payment($user, ['paymongo_payment_intent_id' => 'pi_paid', 'created_at' => $old]);
        $failed = $this->payment($user, ['paymongo_payment_intent_id' => 'pi_failed', 'created_at' => $old]);
        $waiting = $this->payment($user, ['paymongo_payment_intent_id' => 'pi_waiting', 'created_at' => $old]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame('paid', $paid->fresh()->status);
        $this->assertSame('failed', $failed->fresh()->status);
        $this->assertSame('pending', $waiting->fresh()->status);
        Notification::assertSentToTimes($user, PaymentReceipt::class, 1);
        Notification::assertSentToTimes($user, PaymentFailed::class, 1);
    }

    public function test_reconcile_ignores_fresh_payments_dry_runs_and_a_disabled_gateway(): void
    {
        Notification::fake();
        $this->enableGateway();
        Http::fake(['api.paymongo.com/*' => $this->intent('pi_x', 'succeeded')]);
        $user = $this->commuter();
        $fresh = $this->payment($user, ['paymongo_payment_intent_id' => 'pi_x', 'created_at' => now()->subMinute()]);
        $old = $this->payment($user, ['paymongo_payment_intent_id' => 'pi_x', 'created_at' => now()->subMinutes(30)]);

        $this->artisan('payments:reconcile --dry-run')->assertSuccessful();
        $this->assertSame('pending', $old->fresh()->status, 'dry run changes nothing');

        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertSame('pending', $fresh->fresh()->status, 'too new: the webhook may still arrive');
        $this->assertSame('paid', $old->fresh()->status);

        config(['paymongo.enabled' => false]);
        PayMongoService::flushConfigCache();
        $again = $this->payment($user, ['paymongo_payment_intent_id' => 'pi_x', 'created_at' => now()->subMinutes(30)]);
        $this->artisan('payments:reconcile')->expectsOutput('The payment gateway is not enabled; nothing to reconcile.')->assertSuccessful();
        $this->assertSame('pending', $again->fresh()->status);
    }

    public function test_gateway_errors_during_reconcile_alert_the_admins_once(): void
    {
        Notification::fake();
        $this->enableGateway();
        Http::fake(['api.paymongo.com/*' => Http::response(['errors' => [['detail' => 'down']]], 500)]);
        $admin = $this->staff('admin');
        $user = $this->commuter();

        foreach (range(1, 5) as $i) {
            $this->payment($user, ['paymongo_payment_intent_id' => 'pi_'.$i, 'created_at' => now()->subMinutes(30)]);
        }

        $this->artisan('payments:reconcile')->assertSuccessful();

        Notification::assertSentToTimes($admin, GatewayAlert::class, 1);
    }

    public function test_gateway_monitor_alerts_at_the_threshold_and_only_once_per_window(): void
    {
        Notification::fake();
        config(['alerts.gateway_failure_threshold' => 3, 'alerts.gateway_failure_window_minutes' => 15]);
        $admin = $this->staff('admin');
        $monitor = app(GatewayMonitor::class);

        $monitor->recordFailure('one');
        $monitor->recordFailure('two');
        Notification::assertNotSentTo($admin, GatewayAlert::class);

        $monitor->recordFailure('three');
        $monitor->recordFailure('four');
        $monitor->recordFailure('five');

        Notification::assertSentToTimes($admin, GatewayAlert::class, 1);
        Notification::assertSentTo($admin, GatewayAlert::class, fn ($n) => $n->failures === 3 && $n->lastContext === 'three');
    }

    public function test_a_checkout_that_cannot_be_opened_counts_as_a_gateway_failure(): void
    {
        Notification::fake();
        $this->enableGateway();
        config(['alerts.gateway_failure_threshold' => 1]);
        Http::fake(['api.paymongo.com/*' => Http::response(['errors' => [['detail' => 'nope']]], 500)]);
        $fare = Fare::factory()->create();
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 1, 'regular' => 15, 'discount' => 12]);
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 5, 'regular' => 17.25, 'discount' => 13.75]);
        $admin = $this->staff('admin');
        $user = $this->commuter();

        $this->actingAs($user)->post(route('payment.process'), [
            'pickup' => 'A', 'destination' => 'B', 'distance' => '5.2', 'amount' => 18,
            'payment-method' => 'GCash', 'transaction-id' => '#SC-GWFAIL',
        ])->assertSessionHas('error');

        Notification::assertSentTo($admin, GatewayAlert::class);
    }

    // ── QR location checks ───────────────────────────────────────────────

    private function broadcasting(array $at = self::NEAR, array $vehicleState = []): array
    {
        [$driverUser, $driver] = $this->driver();
        $vehicle = Vehicle::factory()->withDriver($driver)->create(array_merge(['status' => 'active'], $vehicleState));
        VehicleLocation::create([
            'vehicle_id' => $vehicle->id, 'latitude' => $at[0], 'longitude' => $at[1], 'last_update' => now(), 'user_id' => $driverUser->id,
        ]);

        return [$vehicle, $driver];
    }

    public function test_finder_matches_the_nearest_broadcasting_vehicle_and_its_driver(): void
    {
        [$near, $driver] = $this->broadcasting([self::NEAR[0] + 0.0005, self::NEAR[1]]); // about 55 m
        $this->broadcasting([self::NEAR[0] + 0.0010, self::NEAR[1]]);                     // about 110 m

        $match = app(NearbyVehicleFinder::class)->find(...self::NEAR);

        $this->assertSame($near->id, $match['vehicle']->id);
        $this->assertSame($driver->id, $match['driver']->id);
        $this->assertEqualsWithDelta(55, $match['distance_m'], 5);
    }

    public function test_finder_ignores_far_stale_and_out_of_service_vehicles(): void
    {
        $finder = app(NearbyVehicleFinder::class);

        $this->broadcasting([self::NEAR[0] + 0.01, self::NEAR[1]]); // about 1.1 km away
        $this->assertNull($finder->find(...self::NEAR));

        [$stale] = $this->broadcasting();
        VehicleLocation::where('vehicle_id', $stale->id)->update(['last_update' => now()->subMinutes(30)]);
        $this->assertNull($finder->find(...self::NEAR));

        $this->broadcasting(self::NEAR, ['status' => 'maintenance']);
        $this->assertNull($finder->find(...self::NEAR));
    }

    public function test_a_vague_gps_fix_widens_the_radius_a_little(): void
    {
        $this->broadcasting([self::NEAR[0] + 0.0016, self::NEAR[1]]); // about 178 m

        $finder = app(NearbyVehicleFinder::class);
        $this->assertNull($finder->find(...self::NEAR));
        $this->assertNotNull($finder->find(self::NEAR[0], self::NEAR[1], 60));
    }

    public function test_locate_ties_the_scan_to_the_vehicle_without_keeping_the_coordinates(): void
    {
        config(['qr.required' => true]);
        [$vehicle, $driver] = $this->broadcasting();
        $user = $this->commuter();
        QrScan::record($user->id, QrCode::create([]));

        $this->actingAs($user)->postJson(route('payment.scan.locate'), ['latitude' => self::NEAR[0], 'longitude' => self::NEAR[1], 'accuracy' => 12])
            ->assertOk()->assertJson(['matched' => true, 'plate_number' => $vehicle->plate_number]);

        $scan = QrScan::currentFor($user->id);
        $this->assertSame($vehicle->id, $scan->vehicle_id);
        $this->assertSame($driver->id, $scan->driver_id);
        $this->assertNotNull($scan->located_at);
        $this->assertNull(collect($scan->getAttributes())->keys()->first(fn ($k) => in_array($k, ['latitude', 'longitude'], true)), 'the commuter position is not stored');
    }

    public function test_locate_needs_a_scan_and_valid_coordinates(): void
    {
        config(['qr.required' => true]);
        $user = $this->commuter();

        $this->actingAs($user)->postJson(route('payment.scan.locate'), ['latitude' => 10, 'longitude' => 123])->assertStatus(422);

        QrScan::record($user->id, QrCode::create([]));
        $this->actingAs($user)->postJson(route('payment.scan.locate'), ['latitude' => 'x', 'longitude' => 500])->assertStatus(422);
        $this->actingAs($user)->postJson(route('payment.scan.locate'), ['latitude' => 10, 'longitude' => 123])
            ->assertOk()->assertJson(['matched' => false]);
    }

    private function payOnBoard(User $user, string $tx)
    {
        return $this->actingAs($user)->post(route('payment.process'), [
            'pickup' => 'A', 'destination' => 'B', 'distance' => '5.2', 'amount' => 18,
            'payment-method' => 'Wallet', 'transaction-id' => $tx,
        ]);
    }

    private function fareTable(): void
    {
        $fare = Fare::factory()->create();
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 1, 'regular' => 15, 'discount' => 12]);
        FareRate::factory()->create(['fare_id' => $fare->id, 'km' => 5, 'regular' => 17.25, 'discount' => 13.75]);
    }

    public function test_the_fare_is_attributed_to_the_vehicle_and_driver_the_commuter_rode(): void
    {
        config(['qr.required' => true]);
        $this->fareTable();
        [$vehicle, $driver] = $this->broadcasting();
        $user = $this->commuter();
        QrScan::record($user->id, QrCode::create([]));
        $this->actingAs($user)->postJson(route('payment.scan.locate'), ['latitude' => self::NEAR[0], 'longitude' => self::NEAR[1]]);

        $this->payOnBoard($user, '#SC-ATTR01')->assertSessionHas('success');

        $payment = Payment::where('transaction_id', '#SC-ATTR01')->firstOrFail();
        $this->assertSame($vehicle->id, $payment->vehicle_id);
        $this->assertSame($driver->id, $payment->driver_id);
    }

    public function test_with_the_check_on_a_scan_made_nowhere_near_a_vehicle_cannot_pay(): void
    {
        config(['qr.required' => true, 'qr.require_nearby_vehicle' => true]);
        $this->fareTable();
        $user = $this->commuter();
        QrScan::record($user->id, QrCode::create([]));
        $this->actingAs($user)->postJson(route('payment.scan.locate'), ['latitude' => 10.0, 'longitude' => 120.0])->assertJson(['matched' => false, 'required' => true]);

        $this->payOnBoard($user, '#SC-NOPE01')->assertSessionHas('error');

        $this->assertNull(Payment::where('transaction_id', '#SC-NOPE01')->first());
        $this->assertEquals(500, $user->wallet->fresh()->balance, 'nothing was charged');
        $this->assertNull(QrScan::currentFor($user->id)->used_at, 'the scan is not spent');
    }

    public function test_with_the_check_on_a_scan_that_was_never_located_cannot_pay_either(): void
    {
        config(['qr.required' => true, 'qr.require_nearby_vehicle' => true]);
        $this->fareTable();
        $user = $this->commuter();
        QrScan::record($user->id, QrCode::create([]));

        $this->payOnBoard($user, '#SC-NOLOC1')->assertSessionHas('error');
        $this->assertNull(Payment::where('transaction_id', '#SC-NOLOC1')->first());
    }

    public function test_with_the_check_on_a_located_scan_pays_normally(): void
    {
        config(['qr.required' => true, 'qr.require_nearby_vehicle' => true]);
        $this->fareTable();
        $this->broadcasting();
        $user = $this->commuter();
        QrScan::record($user->id, QrCode::create([]));
        $this->actingAs($user)->postJson(route('payment.scan.locate'), ['latitude' => self::NEAR[0], 'longitude' => self::NEAR[1]]);

        $this->payOnBoard($user, '#SC-OKAY01')->assertSessionHas('success');
    }

    public function test_the_check_is_off_by_default(): void
    {
        $this->assertFalse((bool) config('qr.require_nearby_vehicle'));
    }

    public function test_checkout_page_runs_the_location_check_when_a_scan_exists(): void
    {
        config(['qr.required' => true]);
        $this->fareTable();
        $user = $this->commuter();
        QrScan::record($user->id, QrCode::create([]));

        $this->actingAs($user)->get(route('payment.index', ['pickup' => 'A', 'destination' => 'B', 'distance' => '5.2', 'price-regular' => '18']))
            ->assertOk()->assertSee('vehicle-check', false)->assertSee(route('payment.scan.locate'), false);
    }

    // ── Fare evasion & repeat violators ──────────────────────────────────

    private function abandonedScans(User $user, int $n): void
    {
        $code = QrCode::create([]);
        foreach (range(1, $n) as $i) {
            QrScan::create(['user_id' => $user->id, 'qr_code_id' => $code->id, 'scanned_at' => now()->subHours($i)]);
        }
    }

    public function test_commuters_who_keep_scanning_without_paying_are_flagged_to_the_admins_once(): void
    {
        Notification::fake();
        $admin = $this->staff('admin');
        $user = $this->commuter();
        $this->abandonedScans($user, 3);
        $paying = $this->commuter();
        $this->abandonedScans($paying, 2);

        $row = app(FleetAnalyticsService::class)->abandonedScans()->firstWhere('user_id', $user->id);
        $this->assertSame(3, $row['count']);
        $this->assertNull(app(FleetAnalyticsService::class)->abandonedScans()->firstWhere('user_id', $paying->id));

        $this->artisan('fares:flag-evasion')->assertSuccessful();
        Notification::assertSentTo($admin, FareEvasionFlag::class, fn ($n) => $n->rows->contains('user_id', $user->id));
        $this->assertTrue(Activity::where('event', 'fare_evasion_flagged')->where('subject_id', $user->id)->exists());

        $this->artisan('fares:flag-evasion')->assertSuccessful();
        Notification::assertSentToTimes($admin, FareEvasionFlag::class, 1);
    }

    public function test_a_scan_that_is_still_valid_or_was_paid_for_is_not_abandoned(): void
    {
        $user = $this->commuter();
        $code = QrCode::create([]);
        QrScan::create(['user_id' => $user->id, 'qr_code_id' => $code->id, 'scanned_at' => now()->subMinutes(2)]);
        QrScan::create(['user_id' => $user->id, 'qr_code_id' => $code->id, 'scanned_at' => now()->subHour(), 'used_at' => now()->subMinutes(55)]);
        QrScan::create(['user_id' => $user->id, 'qr_code_id' => $code->id, 'scanned_at' => now()->subHours(2), 'used_at' => now()->subHour()]);

        $this->assertNull(app(FleetAnalyticsService::class)->abandonedScans(1)->firstWhere('user_id', $user->id));
    }

    public function test_repeat_violators_are_flagged_to_the_driver_managers_once(): void
    {
        Notification::fake();
        $manager = $this->staff('driver_manager');
        [$user, $driver] = $this->driver();
        $code = $this->code();

        foreach ([1, 5, 12] as $daysAgo) {
            ViolationLog::create([
                'user_id' => $user->id, 'vc_id' => $code->id, 'violation_instance' => '1', 'violation_fine' => 100,
                'date_of_violation' => today()->subDays($daysAgo)->toDateString(), 'time_of_violation' => '10:00:00', 'place_of_violation' => 'X',
            ]);
        }

        $this->artisan('drivers:flag-repeat-violators')->assertSuccessful();
        Notification::assertSentTo($manager, RepeatViolatorFlag::class, fn ($n) => $n->rows->contains(fn ($r) => $r['driver']->id === $driver->id && $r['count'] === 3));
        $this->assertTrue(Activity::where('event', 'repeat_violator_flagged')->where('subject_id', $driver->id)->exists());

        $this->artisan('drivers:flag-repeat-violators')->assertSuccessful();
        Notification::assertSentToTimes($manager, RepeatViolatorFlag::class, 1);
    }

    public function test_old_violations_do_not_make_a_repeat_violator(): void
    {
        [$user, $driver] = $this->driver();
        $code = $this->code();

        foreach ([40, 50, 60] as $daysAgo) {
            ViolationLog::create([
                'user_id' => $user->id, 'vc_id' => $code->id, 'violation_instance' => '1', 'violation_fine' => 100,
                'date_of_violation' => today()->subDays($daysAgo)->toDateString(), 'time_of_violation' => '10:00:00', 'place_of_violation' => 'X',
            ]);
        }

        $this->assertNull(app(FleetAnalyticsService::class)->repeatViolators()->first(fn ($r) => $r['driver']->id === $driver->id));
    }

    public function test_flag_commands_dry_run_changes_and_sends_nothing(): void
    {
        Notification::fake();
        $this->staff('admin');
        $user = $this->commuter();
        $this->abandonedScans($user, 3);

        $this->artisan('fares:flag-evasion --dry-run')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertFalse(Activity::where('event', 'fare_evasion_flagged')->where('subject_id', $user->id)->exists());
    }

    // ── Analytics ────────────────────────────────────────────────────────

    public function test_broadcasts_record_the_distance_driven_between_pings(): void
    {
        Event::fake([LocationUpdated::class]);
        [$user, $driver] = $this->driver();
        $vehicle = Vehicle::factory()->withDriver($driver)->create(['status' => 'active']);

        $ping = fn (float $lat, int $ts) => $this->actingAs($user)->postJson(route('vehicle.broadcast'), [
            'vehicle_id' => (string) $vehicle->id, 'latitude' => $lat, 'longitude' => self::NEAR[1], 'user_id' => $user->id, 'timestamp' => $ts,
        ])->assertOk();

        $ping(10.3157, 1001);
        $ping(10.3167, 1002); // 0.001° of latitude is about 111 m

        $histories = VehicleLocationHistory::whereHas('vehicleLocation', fn ($q) => $q->where('vehicle_id', $vehicle->id))->orderBy('id')->get();

        $this->assertCount(2, $histories);
        $this->assertEquals(0, $histories[0]->distance_from_last_pos, 'the first ping of a trip has nothing before it');
        $this->assertEqualsWithDelta(0.111, (float) $histories[1]->distance_from_last_pos, 0.01);
    }

    public function test_a_gps_jump_or_a_long_silence_is_not_counted_as_distance(): void
    {
        Event::fake([LocationUpdated::class]);
        [$user, $driver] = $this->driver();
        $vehicle = Vehicle::factory()->withDriver($driver)->create(['status' => 'active']);

        $ping = fn (float $lat, int $ts) => $this->actingAs($user)->postJson(route('vehicle.broadcast'), [
            'vehicle_id' => (string) $vehicle->id, 'latitude' => $lat, 'longitude' => self::NEAR[1], 'user_id' => $user->id, 'timestamp' => $ts,
        ])->assertOk();

        $ping(10.3157, 2001);
        $ping(11.3157, 2002); // 111 km in a moment: a glitch

        $last = VehicleLocationHistory::whereHas('vehicleLocation', fn ($q) => $q->where('vehicle_id', $vehicle->id))->latest('id')->first();
        $this->assertEquals(0, $last->distance_from_last_pos);
    }

    public function test_driver_and_vehicle_stats_add_up(): void
    {
        [$user, $driver] = $this->driver();
        $vehicle = Vehicle::factory()->withDriver($driver)->create(['status' => 'active']);
        $code = $this->code();

        TimeKeeping::create(['driver_id' => $driver->id, 'vehicle_id' => $vehicle->id, 'date' => today()->toDateString(), 'time_in' => '06:00 AM', 'time_out' => '04:00 PM', 'hours_worked' => 10, 'overtime_hours' => 2, 'sick' => 0, 'vacation' => 0]);
        TimeKeeping::create(['driver_id' => $driver->id, 'vehicle_id' => $vehicle->id, 'date' => today()->subDay()->toDateString(), 'time_in' => '06:00 AM', 'time_out' => '02:00 PM', 'hours_worked' => 8, 'overtime_hours' => 0, 'sick' => 0, 'vacation' => 0, 'auto_closed_at' => now()]);
        ViolationLog::create(['user_id' => $user->id, 'vc_id' => $code->id, 'violation_instance' => '1', 'violation_fine' => 250, 'date_of_violation' => today()->toDateString(), 'time_of_violation' => '10:00:00', 'place_of_violation' => 'X']);

        $commuter = $this->commuter();
        $this->payment($commuter, ['status' => 'paid', 'paid_at' => now(), 'price' => 18, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id]);
        $this->payment($commuter, ['status' => 'failed', 'price' => 99, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id]);

        $location = VehicleLocation::create(['vehicle_id' => $vehicle->id, 'latitude' => 10.3, 'longitude' => 123.9, 'last_update' => now(), 'user_id' => $user->id]);
        VehicleLocationHistory::create(['vehicle_location_id' => $location->id, 'latitude' => 10.3, 'longitude' => 123.9, 'user_id' => $user->id, 'distance_from_last_pos' => 2.5]);
        VehicleLocationHistory::create(['vehicle_location_id' => $location->id, 'latitude' => 10.3, 'longitude' => 123.9, 'user_id' => $user->id, 'distance_from_last_pos' => 1.5]);

        $code2 = QrCode::create([]);
        QrScan::create(['user_id' => $commuter->id, 'qr_code_id' => $code2->id, 'vehicle_id' => $vehicle->id, 'scanned_at' => now()->subHour(), 'used_at' => now()]);
        QrScan::create(['user_id' => $commuter->id, 'qr_code_id' => $code2->id, 'vehicle_id' => $vehicle->id, 'scanned_at' => now()->subHour()]);

        $analytics = app(FleetAnalyticsService::class);
        $d = $analytics->driverStats(today()->subDays(6), today())->firstWhere('id', $driver->id);
        $v = $analytics->vehicleStats(today()->subDays(6), today())->firstWhere('id', $vehicle->id);

        $this->assertSame(2, $d['shifts']);
        $this->assertEquals(18, $d['hours']);
        $this->assertEquals(2, $d['overtime']);
        $this->assertSame(1, $d['auto_closed']);
        $this->assertSame(1, $d['violations']);
        $this->assertEquals(250, $d['fines']);
        $this->assertSame(1, $d['fares'], 'a failed payment is not a fare');
        $this->assertEquals(18, $d['revenue']);

        $this->assertEquals(4.0, $v['km']);
        $this->assertEquals(18, $v['hours']);
        $this->assertSame(1, $v['fares']);
        $this->assertSame(2, $v['scans']);
        $this->assertSame(50, $v['collection_rate']);
    }

    public function test_overtime_trend_covers_the_last_weeks_oldest_first(): void
    {
        [, $driver] = $this->driver();
        TimeKeeping::create(['driver_id' => $driver->id, 'date' => today()->toDateString(), 'time_in' => '06:00 AM', 'time_out' => '06:00 PM', 'hours_worked' => 12, 'overtime_hours' => 4, 'sick' => 0, 'vacation' => 0]);

        $trend = app(FleetAnalyticsService::class)->overtimeTrend(8);

        $this->assertCount(8, $trend);
        $this->assertTrue(Carbon::parse($trend[0]['week'])->lt(Carbon::parse($trend[7]['week'])));
        $this->assertSame(now()->startOfWeek()->toDateString(), $trend[7]['week']);
        $this->assertGreaterThanOrEqual(12, $trend[7]['hours']);
        $this->assertGreaterThanOrEqual(4, $trend[7]['overtime']);
    }

    public function test_analytics_page_is_for_the_admin_only(): void
    {
        $this->actingAs($this->staff('admin'))->get(route('analytics.index'))
            ->assertOk()->assertSee('Fleet')->assertSee('Analytics');
        $this->actingAs($this->staff('admin'))->get(route('analytics.index', ['days' => 90]))->assertOk();
        $this->actingAs($this->staff('admin'))->get(route('analytics.index', ['days' => 5]))->assertOk(); // falls back to 30

        foreach (['commuter', 'driver', 'driver_manager', 'maintenance_manager'] as $role) {
            $this->actingAs($this->staff($role))->get(route('analytics.index'))->assertForbidden();
        }
    }

    // ── Scheduled reports ────────────────────────────────────────────────

    public function test_report_periods_are_the_last_full_week_and_month(): void
    {
        [$from, $to] = PeriodReportService::periodFor('weekly', Carbon::parse('2026-10-26 06:00'));
        $this->assertSame(['2026-10-19', '2026-10-25'], [$from->toDateString(), $to->toDateString()]);

        [$from, $to] = PeriodReportService::periodFor('monthly', Carbon::parse('2026-11-01 06:30'));
        $this->assertSame(['2026-10-01', '2026-10-31'], [$from->toDateString(), $to->toDateString()]);

        [$from, $to] = PeriodReportService::periodFor('monthly', Carbon::parse('2026-03-01'));
        $this->assertSame(['2026-02-01', '2026-02-28'], [$from->toDateString(), $to->toDateString()]);
    }

    private function seedReportPeriod(): array
    {
        // A week long ago, so whatever is in the development database cannot bleed into the totals.
        $from = Carbon::parse('2019-03-04');
        $to = Carbon::parse('2019-03-10');
        $user = $this->commuter();
        $this->payment($user, ['status' => 'paid', 'paid_at' => '2019-03-05 10:00:00', 'price' => 20, 'payment_method' => 'GCash']);
        $this->payment($user, ['status' => 'paid', 'paid_at' => '2019-03-06 10:00:00', 'price' => 30, 'payment_method' => 'Wallet']);
        $this->payment($user, ['status' => 'failed', 'failed_at' => '2019-03-07 10:00:00', 'price' => 99]);
        $wallet = $user->wallet;
        \App\Models\TopupHistory::create(['user_id' => $user->id, 'wallet_id' => $wallet->id, 'amount_added' => 100, 'payment_method' => 'gcash', 'status' => 'completed'])
            ->forceFill(['created_at' => '2019-03-08 09:00:00'])->save();

        [$driverUser, $driver] = $this->driver();
        $code = $this->code();
        ViolationLog::create(['user_id' => $driverUser->id, 'vc_id' => $code->id, 'violation_instance' => '1', 'violation_fine' => 500, 'date_of_violation' => '2019-03-05', 'time_of_violation' => '10:00:00', 'place_of_violation' => 'X', 'payment_status' => 'paid', 'paid_at' => '2019-03-06 12:00:00']);
        TimeKeeping::create(['driver_id' => $driver->id, 'date' => '2019-03-05', 'time_in' => '06:00 AM', 'time_out' => '04:00 PM', 'hours_worked' => 10, 'overtime_hours' => 2, 'sick' => 0, 'vacation' => 0]);

        return [$from, $to];
    }

    public function test_period_report_totals(): void
    {
        [$from, $to] = $this->seedReportPeriod();

        $report = app(PeriodReportService::class)->build($from, $to);

        $this->assertEquals(50, $report['revenue']['total']);
        $this->assertSame(2, $report['revenue']['count']);
        $this->assertEquals(['GCash' => 20, 'Wallet' => 30], collect($report['revenue']['by_method'])->pluck('revenue', 'method')->all());
        $this->assertSame(1, $report['payments']['failed']);
        $this->assertEquals(100, $report['topups']['total']);
        $this->assertSame(1, $report['violations']['count']);
        $this->assertEquals(500, $report['violations']['issued']);
        $this->assertEquals(500, $report['violations']['collected']);
        $this->assertEquals(10, $report['time']['hours']);
        $this->assertEquals(2, $report['time']['overtime']);
        $this->assertSame('Mar 4 – Mar 10, 2019', $report['label']);
    }

    public function test_period_report_csv_is_spreadsheet_safe(): void
    {
        [$from, $to] = $this->seedReportPeriod();
        $service = app(PeriodReportService::class);

        $csv = $service->summaryCsv($service->build($from, $to));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Fares,"Revenue (PHP)",50', $csv);
        $this->assertStringContainsString('Violations,"Fines issued (PHP)",500', $csv);
    }

    public function test_weekly_report_is_emailed_to_the_admins_with_attachments_and_audited(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-10-26 06:00:00'));
        $admin = $this->staff('admin');
        $other = $this->staff('driver_manager');

        $this->artisan('reports:send weekly')->assertSuccessful();

        Notification::assertSentTo($admin, ScheduledReport::class, function ($n) {
            return $n->kind === 'weekly'
                && $n->report['label'] === 'Oct 19 – Oct 25, 2026'
                && collect($n->attachments)->keys()->contains(fn ($k) => str_starts_with($k, 'smartcommute_weekly_report_2026-10-19_to_2026-10-25'));
        });
        Notification::assertNotSentTo($other, ScheduledReport::class);
        $this->assertTrue(Activity::where('event', 'report_sent')->exists());
    }

    public function test_report_mail_summarises_the_figures(): void
    {
        [$from, $to] = $this->seedReportPeriod();
        $admin = $this->staff('admin');
        $report = app(PeriodReportService::class)->build($from, $to);

        $mail = (new ScheduledReport('weekly', $report, ['x.csv' => 'a,b']))->toMail($admin);
        $text = implode("\n", $mail->introLines);

        $this->assertStringContainsString('Weekly report', $mail->subject);
        $this->assertStringContainsString('₱50.00', $text);
        $this->assertStringContainsString('₱100.00', $text);
        $this->assertCount(1, $mail->rawAttachments);
    }

    public function test_report_command_options(): void
    {
        Notification::fake();
        $admin = $this->staff('admin');
        $target = $this->staff('admin');

        $this->artisan('reports:send fortnightly')->assertExitCode(2);

        $this->artisan('reports:send monthly --dry-run')->assertSuccessful();
        Notification::assertNothingSent();

        $this->artisan('reports:send monthly --to='.$target->email)->assertSuccessful();
        Notification::assertSentTo($target, ScheduledReport::class);
        Notification::assertNotSentTo($admin, ScheduledReport::class);
    }

    public function test_activity_log_attachment_can_be_switched_off(): void
    {
        Notification::fake();
        config(['reports.attach_activity_log' => false]);
        $admin = $this->staff('admin');

        $this->artisan('reports:send weekly')->assertSuccessful();

        Notification::assertSentTo($admin, ScheduledReport::class, fn ($n) => count($n->attachments) === 1);
    }

    public function test_new_jobs_are_scheduled(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->pluck('command')->implode("\n");

        foreach (['payments:reconcile', 'drivers:flag-repeat-violators', 'fares:flag-evasion', 'reports:send weekly', 'reports:send monthly'] as $command) {
            $this->assertStringContainsString($command, $events);
        }
    }
}
