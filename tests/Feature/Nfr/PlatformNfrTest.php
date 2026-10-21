<?php

namespace Tests\Feature\Nfr;

use App\Models\TimeKeeping;
use Composer\InstalledVersions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Compatibility, localisation, scalability, maintainability and operations
 * (docs/Non_Functional_Requirements.md §6, §7, §9, §10, §11).
 */
class PlatformNfrTest extends NfrTestCase
{
    // ───────────────────────────── compatibility ─────────────────────────────

    /**
     * @group NFR-COMP-02
     * @group NFR-COMP
     */
    public function test_the_server_runs_on_the_documented_stack()
    {
        $this->assertTrue(version_compare(PHP_VERSION, '8.0.0', '>='), 'PHP 8.0 or newer');
        $this->assertStringStartsWith('9.', app()->version(), 'Laravel 9');
        $this->assertSame('mysql', config('database.default'));
        $this->assertNotNull(DB::connection()->getPdo(), 'the database answers');

        foreach (['laravel/sanctum' => '2.', 'spatie/laravel-permission' => '6.', 'spatie/laravel-activitylog' => '4.', 'pusher/pusher-php-server' => '7.'] as $package => $major) {
            $this->assertTrue(InstalledVersions::isInstalled($package), "{$package} is not installed");
            $this->assertStringStartsWith($major, ltrim(InstalledVersions::getPrettyVersion($package), 'v'), "{$package} major version");
        }
    }

    /**
     * @group NFR-COMP-04
     * @group NFR-COMP
     */
    public function test_front_end_libraries_are_declared_with_a_version_range()
    {
        $package = json_decode(file_get_contents(base_path('package.json')), true);
        $dependencies = ($package['dependencies'] ?? []) + ($package['devDependencies'] ?? []);

        foreach (['maplibre-gl', 'laravel-echo', 'pusher-js'] as $library) {
            $this->assertArrayHasKey($library, $dependencies, "{$library} must be declared");
        }
        foreach ($dependencies as $name => $range) {
            $this->assertMatchesRegularExpression('/^[\^~]?\d/', $range, "{$name} must name a version, not '{$range}'");
        }
    }

    // ───────────────────────────── localisation ─────────────────────────────

    /**
     * @group NFR-LOC-01
     * @group NFR-LOC
     */
    public function test_a_shift_is_stamped_in_philippine_time()
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));

        [$driver] = $this->driverWithVehicle();
        Carbon::setTestNow(Carbon::parse('2026-10-21 02:30:00', 'UTC')); // 10:30 in Manila

        try {
            $this->actingAs($driver)->post(route('driver.timekeeping.clock-in'))->assertSessionHas('success');
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame('10:30 AM', TimeKeeping::where('driver_id', $driver->driver->id)->value('time_in'));
    }

    /**
     * @group NFR-LOC-02
     * @group NFR-LOC
     */
    public function test_a_top_up_must_be_between_ten_and_one_hundred_thousand_pesos()
    {
        $user = $this->commuter(0);
        $topup = fn ($amount) => $this->actingAs($user)->post(route('payment.topup.process'), ['amount' => $amount, 'payment-method' => 'gcash']);

        $topup('9.99')->assertSessionHasErrors('amount');
        $topup('10')->assertSessionHas('success');
        $topup('100000.01')->assertSessionHasErrors('amount');
        $topup('100000')->assertSessionHas('success');
    }

    /**
     * @group NFR-LOC-02
     * @group NFR-LOC
     */
    public function test_money_is_shown_in_pesos()
    {
        $user = $this->commuter(250);

        $this->actingAs($user)->get(route('payment.topup'))->assertOk()->assertSee('₱');
        $this->actingAs($this->userWithRole('admin'))->get(route('reports.generate'))->assertOk()->assertSee('₱');
    }

    /**
     * @group NFR-LOC-03
     * @group NFR-LOC
     */
    public function test_only_philippine_mobile_numbers_are_accepted_as_a_contact_number()
    {
        Storage::fake('public');
        $admin = $this->userWithRole('admin');
        $n = 0;
        $submit = function (string $phone) use ($admin, &$n) {
            $n++;

            return $this->actingAs($admin)->post(route('drivers.store'), [
                'name' => 'Phone Test', 'email' => "phone{$n}@example.com", 'password' => 'password123', 'password_confirmation' => 'password123',
                'license_number' => "LIC-PHONE-{$n}", 'license_code' => 'PRO', 'expiration_date' => '2031-01-01',
                'contact_info' => $phone, 'license_image' => UploadedFile::fake()->image('l.png')->size(100),
            ]);
        };

        foreach (['0917 123 4567', '+63 917 123 4567', '917 123 4567', '09171234567'] as $valid) {
            $submit($valid)->assertSessionDoesntHaveErrors('contact_info');
        }
        foreach (['0817 123 4567', '12345', '+1 917 123 4567', 'call me'] as $invalid) {
            $submit($invalid)->assertSessionHasErrors('contact_info');
        }
    }

    // ───────────────────────────── scalability ─────────────────────────────

    /**
     * @group NFR-SCL-04
     * @group NFR-SCL
     */
    public function test_the_audit_table_is_indexed_for_the_date_filter_and_the_prune()
    {
        $indexes = collect(DB::select('SHOW INDEX FROM activity_log'))->pluck('Key_name')->unique();

        $this->assertTrue($indexes->contains('activity_log_created_at_index'), 'created_at index');
        $this->assertTrue($indexes->contains('activity_log_log_name_index'), 'log_name index');
    }

    // ───────────────────────────── maintainability ─────────────────────────────

    /**
     * @group NFR-MNT-02
     * @group NFR-MNT
     */
    public function test_every_use_case_narrative_points_at_code_that_exists()
    {
        $checked = 0;
        $missing = [];

        foreach (glob(base_path('docs/UCN/UCN_SC_E*.md')) as $sheet) {
            $text = file_get_contents($sheet);
            $this->assertStringContainsString('## Implementation References', $text, basename($sheet).' has no Implementation References');

            $section = explode('## Implementation References', $text, 2)[1];
            preg_match_all('#`((?:app|routes|resources|config|database|tests)/[\w./\-\\\\]+?\.(?:php|js))(?:::[\w()]+)?`#', $section, $paths);

            foreach (array_unique($paths[1]) as $path) {
                $checked++;
                if (! file_exists(base_path($path))) {
                    $missing[] = basename($sheet).' → '.$path;
                }
            }
        }

        $this->assertGreaterThan(100, $checked, 'the narratives should cite plenty of code');
        $this->assertSame([], $missing, 'a narrative cites a file that does not exist');
    }

    /**
     * @group NFR-MNT-03
     * @group NFR-MNT
     */
    public function test_environment_specific_behaviour_is_documented_as_configuration()
    {
        $example = file_get_contents(base_path('.env.example'));

        foreach (['APP_ENV', 'DB_CONNECTION', 'MAIL_MAILER', 'QUEUE_CONNECTION', 'SESSION_DRIVER', 'BROADCAST_DRIVER', 'PAYMONGO_ENABLED', 'ACTIVITY_LOG_RETENTION_DAYS'] as $key) {
            $this->assertMatchesRegularExpression("/^{$key}=/m", $example, "{$key} is missing from .env.example");
        }
        $this->assertMatchesRegularExpression(
            "/env\(\s*'PAYMONGO_ENABLED'\s*,\s*false\s*\)/",
            file_get_contents(base_path('config/paymongo.php')),
            'the payment gateway must default to off — only the environment may turn it on'
        );
    }

    // ───────────────────────────── operations ─────────────────────────────

    /**
     * @group NFR-OPS-03
     * @group NFR-OPS
     */
    public function test_the_fare_guide_import_has_its_python_runtime()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('The Windows path (venv\\Scripts\\python.exe) is checked on that platform.');
        }

        $this->assertFileExists(base_path('venv/bin/python3'), 'without it every fare-guide upload fails');
        $this->assertFileExists(resource_path('scripts/extractPdf.py'));
    }
}
