<?php

namespace Database\Seeders;

use App\Models\DevMarker;
use App\Models\Driver;
use App\Models\Fare;
use App\Models\FareRate;
use App\Models\MaintenanceTask;
use App\Models\Payment;
use App\Models\PreventiveMaintenance;
use App\Models\TimeKeeping;
use App\Models\TopupHistory;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Models\VehicleMaintenanceLog;
use App\Models\ViolationCode;
use App\Models\ViolationLog;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

/**
 * Manual-testing data: a realistic Cebu fleet with commuters, drivers in every
 * approval state, payments in every lifecycle state, time-keeping, violations
 * and maintenance history.
 *
 * Prerequisite: the base seeders have run (`php artisan db:seed`), because this
 * builds on the roles, the maintenance tasks and the published fare table.
 *
 *   /opt/lampp/bin/php artisan db:seed --class=DevSeeder
 *
 * Every seeded account uses the password `admin123`.
 *
 * Safe to re-run. Seeded accounts are reset to their baseline state (password,
 * suspension, approval, today's shift); history that already exists is left
 * alone, so re-running never duplicates rows or wipes what you created while
 * testing.
 */
class DevSeeder extends Seeder
{
    private const PASSWORD = 'admin123';

    /** @var \Illuminate\Support\Collection<int, FareRate> */
    private $rates;

    public function run()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->rates = $this->latestRates();

        if ($this->rates->isEmpty() || MaintenanceTask::count() === 0) {
            $this->command?->error('Run `php artisan db:seed` first: the fare table and maintenance tasks are missing.');

            return;
        }

        DB::transaction(function () {
            $this->violationCodes();
            $this->commuters();
            $this->drivers();
            $this->vehicles();
            $this->payments();
            $this->timeKeeping();
            $this->violations();
            $this->maintenance();
            $this->tracking();
            $this->preferences();
        });

        $this->command?->info('Test data seeded. Every account password: '.self::PASSWORD);
    }

    // ── Accounts ─────────────────────────────────────────────────────────

    private function account(string $email, string $role, array $attrs = []): User
    {
        $user = User::updateOrCreate(['email' => $email], array_merge([
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
            'is_suspended' => false,
            'suspended_at' => null,
        ], $attrs));

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }

    private function commuters(): void
    {
        // email => [state attrs, topups [amount, method, days ago]]
        $commuters = [
            'commuter@gmail.com' => [[], [[500, 'gcash', 30], [200, 'maya', 3]]],
            'maria.santos@example.com' => [[], [[1000, 'gcash', 20], [500, 'maya', 9]]],
            // Never topped up: every wallet payment is refused, GCash/Maya only.
            'juan.delacruz@example.com' => [[], []],
            // Many rows on both history pages, to exercise pagination.
            'ana.reyes@example.com' => [[], [
                [2000, 'gcash', 27], [500, 'maya', 25], [300, 'gcash', 23], [150, 'gcash', 21],
                [250, 'maya', 19], [100, 'gcash', 17], [400, 'maya', 15], [120, 'gcash', 13],
                [600, 'gcash', 11], [80, 'maya', 9], [350, 'gcash', 6], [220, 'maya', 2],
            ]],
            // Suspended but still holding a balance (admin cannot delete until settled).
            'pedro.lim@example.com' => [['is_suspended' => true, 'suspended_at' => now()->subDays(2)], [[100, 'gcash', 12]]],
            // Has not clicked the verification link yet.
            'lara.gomez@example.com' => [['email_verified_at' => null], []],
        ];

        foreach ($commuters as $email => [$attrs, $topups]) {
            $user = $this->account($email, 'commuter', $attrs);
            $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

            if ($topups && TopupHistory::where('user_id', $user->id)->doesntExist()) {
                foreach ($topups as [$amount, $method, $daysAgo]) {
                    $at = now()->subDays($daysAgo)->setTime(10, 15);
                    TopupHistory::create([
                        'user_id' => $user->id,
                        'wallet_id' => $wallet->id,
                        'amount_added' => $amount,
                        'payment_method' => $method,
                        'created_at' => $at,
                        'updated_at' => $at,
                    ]);
                }
            }
        }
    }

    private function drivers(): void
    {
        $drivers = [
            // email => [name, code, licence no, licence code, licence expiry, contact, state, status]
            'driver@gmail.com' => ['driver', 'DRV-000001', 'N01-23-000001', 'PRO', now()->addYears(2), '0917 100 0001', 'approved', 'active'],
            'ramon.delacruz@example.com' => ['Ramon Dela Cruz', 'DRV-000002', 'N02-23-000002', 'PRO', now()->addMonths(18), '0917 100 0002', 'approved', 'active'],
            // Licence lapses in 20 days.
            'liza.mercado@example.com' => ['Liza Mercado', 'DRV-000003', 'N03-23-000003', 'NON-PRO', now()->addDays(20), '0917 100 0003', 'approved', 'inactive'],
            // Licence already expired.
            'dante.cruz@example.com' => ['Dante Cruz', 'DRV-000004', 'N04-21-000004', 'PRO', now()->subDays(45), '0917 100 0004', 'approved', 'inactive'],
            'carlos.bautista@example.com' => ['Carlos Bautista', 'DRV-000005', 'N05-22-000005', 'PRO', now()->addYear(), '0917 100 0005', 'suspended', 'inactive'],
            // Awaiting an admin decision (licence number/code are filled in on approval).
            'elena.torres@example.com' => ['Elena Torres', null, null, null, null, '0917 100 0006', 'pending', 'inactive'],
            'mark.ocampo@example.com' => ['Mark Ocampo', null, null, null, null, '0917 100 0007', 'pending', 'inactive'],
            'nico.tan@example.com' => ['Nico Tan', null, null, null, null, '0917 100 0008', 'rejected', 'inactive'],
        ];

        foreach ($drivers as $email => [$name, $code, $licenseNo, $licenseCode, $expiry, $contact, $state, $status]) {
            $isSuspended = $state === 'suspended';
            $user = $this->account($email, 'driver', $isSuspended
                ? ['is_suspended' => true, 'suspended_at' => now()->subDays(3)]
                : []);

            $existing = Driver::where('user_id', $user->id)->first();

            // The pre-existing demo driver keeps the name it already has.
            $attrs = [
                'name' => $existing?->name ?: $name,
                'driver_code' => $code,
                'license_number' => $licenseNo,
                'license_code' => $licenseCode,
                'expiration_date' => $expiry?->toDateString(),
                'contact_info' => $contact,
                'license_status' => match (true) {
                    $state === 'pending' => 'pending',
                    $state === 'rejected' => 'rejected',
                    $expiry && $expiry->isPast() => 'expired',
                    default => 'approved',
                },
                'is_approved' => in_array($state, ['approved', 'suspended'], true),
                'is_rejected' => $state === 'rejected',
                'is_suspended' => $isSuspended,
                'suspended_at' => $isSuspended ? now()->subDays(3) : null,
                'suspension_reason' => $isSuspended ? 'Reckless driving: under investigation' : null,
                'status' => $status,
                'license_image_path' => $this->licenseImage($user->id, $name),
            ];

            Driver::updateOrCreate(['user_id' => $user->id], $attrs);
        }
    }

    /**
     * A placeholder licence photo, so the approval screen has an image to open.
     * Needs `php artisan storage:link` for the browser to serve it.
     */
    private function licenseImage(int $userId, string $name): ?string
    {
        $path = "licenses/seed-driver-{$userId}.png";

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $img = imagecreatetruecolor(640, 400);
        imagefilledrectangle($img, 0, 0, 640, 400, imagecolorallocate($img, 236, 240, 245));
        imagefilledrectangle($img, 0, 0, 640, 70, imagecolorallocate($img, 30, 64, 120));
        imagestring($img, 5, 24, 26, 'SAMPLE DRIVER LICENSE (TEST DATA)', imagecolorallocate($img, 255, 255, 255));
        imagestring($img, 5, 24, 120, 'Name: '.$name, imagecolorallocate($img, 20, 20, 20));
        imagestring($img, 4, 24, 160, 'User ID: '.$userId, imagecolorallocate($img, 60, 60, 60));
        imagestring($img, 3, 24, 360, 'Generated by DevSeeder - not a real document', imagecolorallocate($img, 120, 120, 120));

        ob_start();
        imagepng($img);
        $png = ob_get_clean();

        Storage::disk('public')->put($path, $png);

        return $path;
    }

    // ── Fleet ────────────────────────────────────────────────────────────

    private function vehicles(): void
    {
        $driver = fn (string $email) => Driver::whereHas('user', fn ($q) => $q->where('email', $email))->value('id');

        $fleet = [
            ['plate_number' => 'ABC-1234', 'year' => 2021, 'brand' => 'Toyota', 'model' => 'Coaster', 'status' => 'active', 'fuel_type' => 'diesel', 'tank_capacity' => '90', 'vin' => 'JTGFB5G10M1000001', 'location' => 'Cebu Branch', 'acquisition_date' => '2021-03-15', 'exp_disposal_date' => '2031-03-15', 'driver' => ['ramon.delacruz@example.com', 'dante.cruz@example.com']],
            ['plate_number' => 'DEF-5678', 'year' => 2022, 'brand' => 'Hyundai', 'model' => 'County', 'status' => 'active', 'fuel_type' => 'diesel', 'tank_capacity' => '80', 'vin' => 'KMFHD37H2NU000002', 'location' => 'Mandaue Depot', 'acquisition_date' => '2022-06-01', 'exp_disposal_date' => '2032-06-01', 'driver' => 'liza.mercado@example.com'],
            // Free to assign to a driver.
            ['plate_number' => 'STU-1122', 'year' => 2023, 'brand' => 'Foton', 'model' => 'Toano', 'status' => 'active', 'fuel_type' => 'diesel', 'tank_capacity' => '75', 'vin' => 'LVBV4PBB6PE000003', 'location' => 'Cebu Branch', 'acquisition_date' => '2023-01-20', 'exp_disposal_date' => '2033-01-20', 'driver' => null],
            ['plate_number' => 'GHI-9012', 'year' => 2019, 'brand' => 'Isuzu', 'model' => 'NPR Modern Jeepney', 'status' => 'maintenance', 'fuel_type' => 'diesel', 'tank_capacity' => '100', 'vin' => 'JAANPR75ZK7000004', 'location' => 'Talisay Garage', 'acquisition_date' => '2019-09-10', 'exp_disposal_date' => '2029-09-10', 'driver' => null],
            ['plate_number' => 'MNO-3456', 'year' => 2018, 'brand' => 'Mitsubishi', 'model' => 'L300', 'status' => 'inactive', 'fuel_type' => 'diesel', 'tank_capacity' => '60', 'vin' => 'MMBJNKB40JD000005', 'location' => 'Mandaue Depot', 'acquisition_date' => '2018-04-05', 'exp_disposal_date' => '2028-04-05', 'driver' => null],
            ['plate_number' => 'PQR-7890', 'year' => 2012, 'brand' => 'Nissan', 'model' => 'Urvan', 'status' => 'disposed', 'fuel_type' => 'diesel', 'tank_capacity' => '65', 'vin' => 'JN1CVUE25U0000006', 'location' => 'Cebu Branch', 'acquisition_date' => '2012-02-14', 'exp_disposal_date' => '2024-02-14', 'driver' => null],
        ];

        foreach ($fleet as $row) {
            $emails = (array) $row['driver'];
            unset($row['driver']);

            $vehicle = Vehicle::updateOrCreate(['plate_number' => $row['plate_number']], $row);

            // One or two drivers per vehicle; two take turns day by day (see dutyRoster()).
            $vehicle->drivers()->sync(array_filter(array_map($driver, $emails)));
        }

        // The pre-existing demo van is the one assigned to driver@gmail.com.
        Vehicle::where('plate_number', 'JKL-7890')->update(['status' => 'active']);

        $this->dutyRoster();
    }

    /**
     * ABC-1234 is shared by Ramon and Dante, who alternate days: the roster covers 30 days either side
     * of today, starting with Ramon on the even-numbered days since 2026-01-01.
     */
    private function dutyRoster(): void
    {
        $vehicle = Vehicle::where('plate_number', 'ABC-1234')->first();
        $drivers = $vehicle?->drivers()->get();

        if (! $drivers || $drivers->count() < 2) {
            return;
        }

        $ramon = $drivers->first(fn ($d) => $d->user?->email === 'ramon.delacruz@example.com') ?? $drivers[0];
        $other = $drivers->firstWhere('id', '!=', $ramon->id);

        for ($i = -30; $i <= 30; $i++) {
            $date = now()->addDays($i)->startOfDay();
            $driverId = ((int) $date->diffInDays(\Illuminate\Support\Carbon::parse('2026-01-01'))) % 2 === 0 ? $ramon->id : $other->id;

            \App\Models\VehicleDutyDay::updateOrCreate(
                ['vehicle_id' => $vehicle->id, 'duty_date' => $date->toDateString()],
                ['driver_id' => $driverId]
            );
        }
    }

    // ── Payments ─────────────────────────────────────────────────────────

    private function payments(): void
    {
        $places = [
            ['Colon Street', 'Ayala Center Cebu', 5.8],
            ['Carbon Market', 'SM Seaside City', 7.4],
            ['Cebu IT Park', 'Mactan-Cebu Airport', 14.2],
            ['Fuente Osmena Circle', 'University of San Carlos', 4.1],
            ['Mandaue City Hall', 'Colon Street', 9.6],
            ['Talisay City Hall', 'Ayala Center Cebu', 11.3],
            ['Ayala Center Cebu', 'Cebu IT Park', 2.3],
            ['SM Seaside City', 'Mandaue City Hall', 12.9],
        ];

        // user => list of [days ago, place index, method, status]
        $plans = [
            'commuter@gmail.com' => [
                [28, 0, 'Wallet', 'paid'], [21, 1, 'Wallet', 'paid'],
                [14, 2, 'GCash', 'paid'], [6, 3, 'Wallet', 'paid'],
                [1, 4, 'Wallet', 'paid'],
            ],
            'maria.santos@example.com' => [
                [13, 0, 'Wallet', 'paid'], [10, 5, 'Maya', 'paid'],
                [7, 6, 'Wallet', 'paid'], [4, 7, 'GCash', 'paid'],
                [2, 1, 'Wallet', 'paid'], [0, 3, 'Wallet', 'paid'],
            ],
            // Wallet is empty. Covers pending, failed and cancelled gateway payments.
            'juan.delacruz@example.com' => [
                [12, 0, 'GCash', 'paid'], [8, 4, 'Maya', 'paid'],
                [5, 2, 'GCash', 'failed'], [3, 6, 'Maya', 'cancelled'],
                [0, 5, 'GCash', 'pending'],
            ],
            'ana.reyes@example.com' => [
                [26, 0, 'Wallet', 'paid'], [24, 1, 'Wallet', 'paid'],
                [22, 2, 'GCash', 'paid'], [20, 3, 'Wallet', 'paid'],
                [18, 4, 'Maya', 'paid'], [16, 5, 'Wallet', 'paid'],
                [14, 6, 'Wallet', 'paid'], [11, 7, 'GCash', 'paid'],
                [9, 0, 'Wallet', 'paid'], [7, 1, 'Wallet', 'paid'],
                [5, 2, 'Maya', 'paid'], [3, 3, 'Wallet', 'paid'],
                [1, 4, 'Wallet', 'paid'], [0, 5, 'GCash', 'paid'],
            ],
            'pedro.lim@example.com' => [
                [11, 6, 'Wallet', 'paid'],
            ],
        ];

        foreach ($plans as $email => $rows) {
            $user = User::where('email', $email)->firstOrFail();
            $wallet = Wallet::where('user_id', $user->id)->firstOrFail();
            $seeded = Payment::where('paid_by', $user->id)->where('transaction_id', 'like', 'SEED-%')->exists();

            foreach ($rows as $i => [$daysAgo, $placeIdx, $method, $status]) {
                [$from, $to, $km] = $places[$placeIdx];
                $tier = $this->tierFor($km);
                $price = (float) ceil($tier->regular);
                $at = now()->subDays($daysAgo)->setTime(7 + ($i * 2) % 12, 20 + $i);
                if ($at->isFuture()) {
                    $at = now()->subMinutes(30 + $i);
                }

                $attrs = [
                    'paid_by' => $user->id,
                    'starting_point' => $from,
                    'destination' => $to,
                    'total_distance' => $km,
                    'is_discounted' => false,
                    'payment_method' => $method,
                    'price' => $price,
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? $at : null,
                    'failed_at' => in_array($status, ['failed', 'cancelled'], true) ? $at : null,
                    'failure_message' => match ($status) {
                        'failed' => 'The payment was not completed at the gateway.',
                        'cancelled' => 'Cancelled at checkout.',
                        default => null,
                    },
                    'created_at' => $at,
                    'updated_at' => $at,
                ];

                if ($status === 'pending') {
                    $attrs['created_at'] = $attrs['updated_at'] = now()->subHours(2);
                }

                if ($method !== 'Wallet') {
                    $attrs['paymongo_payment_intent_id'] = sprintf('pi_seed_%d_%d', $user->id, $i);
                    $attrs['paymongo_checkout_session_id'] = sprintf('cs_seed_%d_%d', $user->id, $i);
                }

                Payment::updateOrCreate(
                    ['transaction_id' => sprintf('SEED-%04d-%02d', $user->id, $i + 1)],
                    $attrs
                );
            }

            // Balance = top-ups received minus settled wallet fares. Only computed on
            // the first run for a user so a re-run never overwrites a tested balance.
            if (! $seeded) {
                $topped = (float) TopupHistory::where('user_id', $user->id)->sum('amount_added');
                $spent = (float) Payment::where('paid_by', $user->id)
                    ->where('payment_method', 'Wallet')->where('status', 'paid')->sum('price');

                $wallet->update(['balance' => max(0, $topped - $spent)]);
            }
        }
    }

    private function latestRates()
    {
        $fare = Fare::orderByDesc('id')->first();

        return $fare ? FareRate::where('fare_id', $fare->id)->orderBy('km')->get() : collect();
    }

    /** Mirrors FareCalculator: highest tier at or below the distance, else the first. */
    private function tierFor(float $km): FareRate
    {
        return $this->rates->filter(fn ($r) => (float) $r->km <= $km)->last() ?? $this->rates->first();
    }

    // ── Time-keeping ─────────────────────────────────────────────────────

    private function timeKeeping(): void
    {
        $approved = Driver::where('is_approved', true)->where('is_rejected', false)->get()->keyBy(
            fn ($d) => $d->user->email
        );

        // [days ago, in, out, hours, overtime, sick, vacation]
        $history = [
            [1, '06:00 AM', '02:05 PM', 8.08, 0.08, 0, 0],
            [2, '06:10 AM', '04:40 PM', 10.5, 2.5, 0, 0],
            [3, '06:00 AM', '02:00 PM', 8.0, 0, 0, 0],
            [4, null, null, 0, 0, 1, 0],
            [5, '07:00 AM', '03:00 PM', 8.0, 0, 0, 0],
            [6, null, null, 0, 0, 0, 1],
            [8, '06:05 AM', '02:15 PM', 8.17, 0.17, 0, 0],
            [9, '06:00 AM', '01:30 PM', 7.5, 0, 0, 0],
        ];

        foreach (['driver@gmail.com', 'ramon.delacruz@example.com', 'liza.mercado@example.com'] as $email) {
            $driver = $approved[$email];

            foreach ($history as [$daysAgo, $in, $out, $hours, $ot, $sick, $vac]) {
                TimeKeeping::updateOrCreate(
                    ['driver_id' => $driver->id, 'date' => now()->subDays($daysAgo)->toDateString()],
                    ['time_in' => $in, 'time_out' => $out, 'hours_worked' => $hours, 'overtime_hours' => $ot, 'sick' => $sick, 'vacation' => $vac]
                );
            }
        }

        // Today: Ramon is mid-shift (test clock-out); driver@gmail.com has not
        // started (test clock-in).
        TimeKeeping::updateOrCreate(
            ['driver_id' => $approved['ramon.delacruz@example.com']->id, 'date' => today()->toDateString()],
            ['time_in' => '06:00 AM', 'time_out' => null, 'hours_worked' => null, 'overtime_hours' => null, 'sick' => 0, 'vacation' => 0]
        );
        TimeKeeping::where('driver_id', $approved['driver@gmail.com']->id)->whereDate('date', today())->delete();
        $approved['driver@gmail.com']->update(['status' => 'inactive']);
    }

    // ── Violations ───────────────────────────────────────────────────────

    private function violationCodes(): void
    {
        // The dev data logs violations against the real PUJ schedule. Only reseed
        // it when it is missing, so re-running never wipes violations logged while testing.
        if (ViolationCode::count() !== count(ViolationCodeSeeder::CODES)) {
            $this->call(ViolationCodeSeeder::class);
        }
    }

    private function violations(): void
    {
        $fine = fn (string $code, int $n) => (float) ViolationCode::where('code', $code)->value(
            ['', 'first_offense', 'second_offense', 'third_offense', 'fourth_offense'][min($n, 4)]
        );

        // user email => [[code, offence no, days ago, time, place, penalty, remarks]]
        $plans = [
            'driver@gmail.com' => [
                ['UV01', 1, 60, '09:30:00', 'Osmena Boulevard', 'Warning', 'Ran a red light at the intersection.'],
                ['UV01', 2, 30, '17:10:00', 'N. Bacalso Avenue', null, 'Second sign violation within 90 days.'],
                ['UV11', 1, 14, '07:45:00', 'Colon Street', 'Community service', 'Eight passengers over capacity.'],
                ['UV02', 1, 5, '22:05:00', 'South Road Properties', 'Suspension: 7 days', 'Overspeeding near the coastal road.'],
            ],
            'ramon.delacruz@example.com' => [
                ['UV13', 1, 20, '08:15:00', 'Mandaue City Hall', 'Warning', 'Fare matrix not displayed.'],
                ['UV09', 1, 9, '18:40:00', 'Ayala Center Cebu', null, 'Refused a PWD passenger.'],
            ],
            'dante.cruz@example.com' => [
                ['UV06', 1, 40, '11:00:00', 'Talisay City', 'Impound: 3 days', 'Franchise paper not available at inspection.'],
            ],
            'carlos.bautista@example.com' => [
                ['UV02', 1, 12, '23:20:00', 'Mactan Bridge', 'Suspension: 30 days', 'Racing another vehicle.'],
                ['UV02', 2, 4, '21:55:00', 'Cebu South Coastal Road', 'Suspension: 60 days', 'Repeat offence.'],
            ],
        ];

        foreach ($plans as $email => $rows) {
            $user = User::where('email', $email)->firstOrFail();

            if (ViolationLog::where('user_id', $user->id)->exists()) {
                continue;
            }

            foreach ($rows as [$code, $n, $daysAgo, $time, $place, $penalty, $remarks]) {
                $at = now()->subDays($daysAgo);
                ViolationLog::create([
                    'user_id' => $user->id,
                    'vc_id' => ViolationCode::where('code', $code)->value('id'),
                    'violation_instance' => (string) $n,
                    'violation_fine' => $fine($code, $n),
                    // Fines a month old or more have been settled; recent ones are still owed.
                    'payment_status' => $daysAgo >= 30 ? ViolationLog::PAID : ViolationLog::UNPAID,
                    'paid_at' => $daysAgo >= 30 ? $at->copy()->addDays(3) : null,
                    'additional_penalties' => $penalty,
                    'date_of_violation' => $at->toDateString(),
                    'time_of_violation' => $time,
                    'place_of_violation' => $place,
                    'remarks' => $remarks,
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);
            }
        }
    }

    // ── Maintenance ──────────────────────────────────────────────────────

    private function maintenance(): void
    {
        $task = fn (string $name) => MaintenanceTask::where('tasks_performed', $name)->value('id');
        $year = now()->year;

        // plate => [[task, month, day, odometer, cost, by, invoice, remarks]]
        // Odometers rise with the date. The schedule keeps one row per
        // vehicle/task (the latest service), so older services such as the
        // March oil change on DEF-5678 show as overdue against their interval.
        $history = [
            'ABC-1234' => [
                ['Oil and Filter Change', 1, 12, 41200, 3200.00, 'Cebu Auto Care', 'INV-1001', 'Synthetic oil.'],
                ['Tire Rotation/Balance', 2, 20, 42950, 1800.00, 'Cebu Auto Care', 'INV-1020', null],
                ['Brake Pad Replacement', 4, 8, 45100, 6400.50, 'Mandaue Brake Center', 'INV-2044', 'Front and rear.'],
                ['Air Filter Change', 6, 3, 47300, 950.00, 'Cebu Auto Care', 'INV-2310', null],
                ['Oil and Filter Change', 8, 15, 50210, 3350.00, 'Cebu Auto Care', 'INV-3011', null],
            ],
            'DEF-5678' => [
                ['Oil and Filter Change', 3, 5, 22100, 3100.00, 'Mandaue Depot Shop', 'INV-1500', null],
                ['Fuel Filter Change', 5, 22, 24800, 2200.00, 'Mandaue Depot Shop', 'INV-1830', null],
                ['Tire Alignment', 7, 30, 27650, 1500.00, 'Mandaue Depot Shop', 'INV-2102', 'Pulling to the left.'],
            ],
            'GHI-9012' => [
                ['Engine Coolant', 5, 2, 88000, 4200.00, 'Talisay Garage', 'INV-0909', null],
                ['Transmission Fluid', 9, 10, 91500, 7800.00, 'Talisay Garage', 'INV-0977', 'Currently in the shop.'],
            ],
            'JKL-7890' => [
                ['Oil and Filter Change', 7, 14, 120300, 3000.00, 'Cebu Auto Care', 'INV-0750', null],
                ['Battery Replacement', 9, 1, 123900, 5600.00, 'Cebu Auto Care', 'INV-0802', null],
            ],
        ];

        foreach ($history as $plate => $services) {
            $vehicle = Vehicle::where('plate_number', $plate)->firstOrFail();

            foreach ($services as [$name, $month, $day, $odo, $cost, $by, $invoice, $remarks]) {
                $date = now()->setDate($year, $month, $day)->startOfDay();
                if ($date->isFuture()) {
                    continue;
                }

                $maintenance = PreventiveMaintenance::updateOrCreate(
                    ['vehicle_id' => $vehicle->id, 'task_id' => $task($name)],
                    [
                        'last_service_odo' => $odo,
                        'last_service_date' => $date->toDateString(),
                        'last_service_cost' => $cost,
                        'comments' => $remarks,
                    ]
                );

                // The log keeps every service, including the ones the schedule has since overwritten.
                VehicleMaintenanceLog::updateOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'maintenance_task_id' => $task($name),
                        'service_date' => $date->toDateString(),
                        'mileage_at_service' => $odo,
                    ],
                    [
                        'maintenance_id' => $maintenance->id,
                        'performed_by' => $by,
                        'cost' => $cost,
                        'invoice_number' => $invoice,
                        'remarks' => $remarks,
                    ]
                );
            }
        }
    }

    // ── Tracking, preferences ────────────────────────────────────

    /**
     * Fresh pings for the live map. The map only lists vehicles heard from in
     * the last 5 minutes, so these go stale quickly: re-run the seeder (or
     * broadcast from a clocked-in driver) to bring them back.
     */
    private function tracking(): void
    {
        $pings = [
            ['ABC-1234', 'ramon.delacruz@example.com', 10.2935, 123.9020, 18.4],
            ['DEF-5678', 'liza.mercado@example.com', 10.3157, 123.8854, 0.0],
            ['JKL-7890', 'driver@gmail.com', 10.3181, 123.9050, 24.1],
        ];

        foreach ($pings as [$plate, $email, $lat, $lng, $speed]) {
            $vehicle = Vehicle::where('plate_number', $plate)->first();
            $user = User::where('email', $email)->first();

            if (! $vehicle || ! $user) {
                continue;
            }

            VehicleLocation::updateOrCreate(
                ['vehicle_id' => (string) $vehicle->id, 'broadcast_timestamp' => 1700000000000 + $vehicle->id],
                [
                    'user_id' => $user->id,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'speed' => $speed,
                    'accuracy' => 12.0,
                    'last_update' => now(),
                ]
            );
        }

        $admin = User::where('email', 'admin@gmail.com')->first();

        if ($admin && DevMarker::where('user_id', $admin->id)->doesntExist()) {
            foreach ([
                ['Dev Jeep 1', 10.3000, 123.9000, 'active'],
                ['Dev Jeep 2', 10.3200, 123.8900, 'active'],
                ['Dev Jeep 3', 10.2900, 123.8800, 'inactive'],
            ] as [$name, $lat, $lng, $status]) {
                DevMarker::create(['user_id' => $admin->id, 'name' => $name, 'lat' => $lat, 'lng' => $lng, 'status' => $status]);
            }
        }
    }

    private function preferences(): void
    {
        foreach ([
            'maria.santos@example.com' => ['light', 11],
            'ana.reyes@example.com' => ['dark', 14],
        ] as $email => [$theme, $size]) {
            UserPreference::updateOrCreate(
                ['user_id' => User::where('email', $email)->value('id')],
                ['theme' => $theme, 'font_size' => $size]
            );
        }
    }
}
