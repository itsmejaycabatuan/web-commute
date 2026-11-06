<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\MaintenanceTask;
use App\Models\Payment;
use App\Models\PreventiveMaintenance;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * UCN_SC_E020 — the Reports & Analytics page. Three things made it look broken: the driver status chart was always
 * empty, today's payments never counted, and the maintenance report called the date of the last service the due date.
 */
class ReportsPageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'driver']);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()])->assignRole('admin');

        return $admin;
    }

    private function driver(string $status, string $name): Driver
    {
        $user = User::factory()->create(['email_verified_at' => now()])->assignRole('driver');

        return Driver::factory()->approved()->create(['user_id' => $user->id, 'name' => $name, 'status' => $status, 'is_suspended' => $status === 'suspended']);
    }

    private function paid(float $price, $at): Payment
    {
        return Payment::factory()->create(['price' => $price, 'status' => 'paid', 'paid_at' => $at, 'payment_method' => 'GCash']);
    }

    // ── Driver performance ───────────────────────────────────────────────

    public function test_the_driver_status_chart_counts_each_status_and_is_not_blank()
    {
        $admin = $this->admin();
        $before = $this->actingAs($admin)->get(route('reports.generate', ['type' => 'driver']))->viewData('driverStatusData');

        $this->driver('active', 'Zed Active');
        $this->driver('suspended', 'Zed Suspended');
        $this->driver('suspended', 'Zed Suspended Too');
        $this->driver('inactive', 'Zed Inactive');

        $response = $this->actingAs($admin)->get(route('reports.generate', ['type' => 'driver']))->assertOk();
        $after = $response->viewData('driverStatusData');

        $this->assertSame($before[0] + 1, $after[0], 'active');
        $this->assertSame($before[1] + 1, $after[1], 'inactive');
        $this->assertSame($before[2] + 2, $after[2], 'suspended');
        $this->assertGreaterThan(0, array_sum($after), 'the doughnut has something to draw');
        $this->assertSame(Driver::count(), array_sum($after), 'every driver is in exactly one slice');
        $this->assertSame(['Active', 'Inactive', 'Suspended'], $response->viewData('driverStatusLabels'));
    }

    public function test_the_top_performers_table_shows_the_real_driver_status()
    {
        $this->driver('active', 'Aaa Working');
        $this->driver('suspended', 'Aaa Removed');

        // Only five show, ranked by hours; give these two the most so they are on the list.
        foreach (Driver::whereIn('name', ['Aaa Working', 'Aaa Removed'])->get() as $d) {
            \App\Models\TimeKeeping::create(['driver_id' => $d->id, 'date' => today()->toDateString(), 'time_in' => '06:00 AM', 'time_out' => '02:00 PM', 'hours_worked' => 999, 'overtime_hours' => 0, 'sick' => 0, 'vacation' => 0]);
        }

        $response = $this->actingAs($this->admin())->get(route('reports.generate', ['type' => 'driver']))->assertOk();
        $rows = collect($response->viewData('topPerformers'))->keyBy('name');

        $this->assertSame('active', $rows['Aaa Working']['status']);
        $this->assertSame('suspended', $rows['Aaa Removed']['status']);

        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/Aaa Working.*?>Active<\/span>/s', $html);
        $this->assertMatchesRegularExpression('/Aaa Removed.*?>Suspended<\/span>/s', $html);
    }

    // ── Financial overview ───────────────────────────────────────────────

    public function test_todays_payments_count_when_the_report_ends_today()
    {
        $admin = $this->admin();
        $start = $this->actingAs($admin)->get(route('reports.generate', ['type' => 'financial', 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString()]))
            ->viewData('financialStats');

        $this->paid(123.45, now()->setTime(15, 30));

        $stats = $this->actingAs($admin)->get(route('reports.generate', ['type' => 'financial', 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString()]))
            ->assertOk()->viewData('financialStats');

        $this->assertSame($start['tx_count'] + 1, $stats['tx_count'], 'a payment at 3:30 pm on the last day is in the range');
        $this->assertEqualsWithDelta($start['total_revenue'] + 123.45, (float) $stats['total_revenue'], 0.001);
    }

    public function test_opening_the_page_reports_the_last_seven_days_the_boxes_show()
    {
        $admin = $this->admin();
        $before = $this->actingAs($admin)->get(route('reports.generate'))->viewData('financialStats')['tx_count'];

        $this->paid(50, now()->subDays(3));
        $this->paid(60, now()->subDays(30));   // outside the default week

        $response = $this->actingAs($admin)->get(route('reports.generate'))->assertOk();

        $this->assertSame($before + 1, $response->viewData('financialStats')['tx_count']);
        $this->assertCount(8, $response->viewData('chartLabels'), 'seven days ago up to and including today');
        $response->assertSee(now()->subDays(7)->toDateString(), false)->assertSee(now()->toDateString(), false);
    }

    public function test_the_csv_export_includes_the_last_day_and_the_default_week()
    {
        $this->paid(77.77, now()->setTime(18, 0));
        $csv = $this->actingAs($this->admin())->get(route('reports.export', ['type' => 'financial']))->assertOk()->streamedContent();

        $this->assertStringContainsString('77.77', $csv);
        $this->assertStringContainsString('Fare Payment', $csv);
    }

    // ── Maintenance & fleet ──────────────────────────────────────────────

    private function service(Vehicle $vehicle, ?int $months, $lastDate, ?int $km = null, ?int $lastOdo = 1000, string $name = 'Oil change'): PreventiveMaintenance
    {
        $task = MaintenanceTask::create(['tasks_performed' => $name, 'months_between_service' => $months, 'miles_between_service' => $km]);

        return PreventiveMaintenance::create([
            'vehicle_id' => $vehicle->id, 'task_id' => $task->id, 'last_service_odo' => $lastOdo,
            'last_service_date' => $lastDate, 'last_service_cost' => 500, 'comments' => 'x',
        ]);
    }

    public function test_the_maintenance_report_uses_the_real_due_date_not_the_last_service_date()
    {
        $admin = $this->admin();
        $stats = $this->actingAs($admin)->get(route('reports.generate', ['type' => 'maintenance']))->viewData('maintStats');

        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        // Done a month ago, due every 6 months: NOT overdue (the old code called every past service overdue).
        $fine = $this->service($vehicle, 6, now()->subMonth()->toDateString(), null, 1000, 'Fine task');
        // Done 3 months ago, due every month: two months overdue.
        $late = $this->service($vehicle, 1, now()->subMonths(3)->toDateString(), null, 900, 'Late task');
        // No interval at all: nothing to schedule, and must not crash the page.
        $none = $this->service($vehicle, null, now()->subMonths(2)->toDateString(), null, 800, 'Loose task');

        $response = $this->actingAs($admin)->get(route('reports.generate', ['type' => 'maintenance']))->assertOk();
        $after = $response->viewData('maintStats');
        $rows = collect($response->viewData('upcomingSchedule'))->keyBy('task_name');

        $this->assertSame($stats['overdue'] + 1, $after['overdue'], 'only the late task is overdue');
        $this->assertSame($stats['pending'] + 1, $after['pending'], 'the fine task is pending; the loose one has nothing scheduled');

        $this->assertSame(now()->subMonth()->addMonths(6)->toDateString(), $rows['Fine task']['due_date']);
        $this->assertSame('Scheduled', $rows['Fine task']['status_label']);
        $this->assertSame('Overdue', $rows['Late task']['status_label']);
        $this->assertSame('N/A', $rows['Loose task']['due_date']);
        $this->assertSame('No interval', $rows['Loose task']['status_label']);

        // Most urgent first: the overdue task is listed before the comfortable one.
        $names = $rows->keys()->all();
        $this->assertLessThan(array_search('Fine task', $names), array_search('Late task', $names));

        $response->assertSee('Late task')->assertSee('Overdue')->assertSee('No interval');
    }

    public function test_a_service_past_its_odometer_reading_is_overdue()
    {
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        // Due every 5,000 km from 10,000: due at 15,000. A later service on the same vehicle logged 16,000.
        $this->service($vehicle, null, now()->subMonth()->toDateString(), 5000, 10000, 'Distance task');
        $this->service($vehicle, 12, now()->toDateString(), null, 16000, 'Later task');

        $rows = collect($this->actingAs($this->admin())->get(route('reports.generate', ['type' => 'maintenance']))->viewData('upcomingSchedule'))->keyBy('task_name');

        $this->assertSame('Overdue', $rows['Distance task']['status_label']);
        $this->assertSame('Scheduled', $rows['Later task']['status_label']);
    }

    public function test_a_disposed_vehicle_is_left_out_of_the_maintenance_report()
    {
        $gone = Vehicle::factory()->create(['status' => 'disposed']);
        $this->service($gone, 1, now()->subMonths(5)->toDateString(), null, 1000, 'Gone task');

        $rows = collect($this->actingAs($this->admin())->get(route('reports.generate', ['type' => 'maintenance']))->viewData('upcomingSchedule'));

        $this->assertNull($rows->firstWhere('task_name', 'Gone task'));
    }

    public function test_the_maintenance_csv_matches_the_report()
    {
        $vehicle = Vehicle::factory()->create(['status' => 'active']);
        $this->service($vehicle, 1, now()->subMonths(3)->toDateString(), null, 900, 'Csv late task');

        $csv = $this->actingAs($this->admin())->get(route('reports.export', ['type' => 'maintenance']))->assertOk()->streamedContent();

        $this->assertMatchesRegularExpression('/'.preg_quote($vehicle->plate_number, '/').',"?Csv late task"?,'.now()->subMonths(2)->toDateString().',Overdue/', $csv);
    }
}
