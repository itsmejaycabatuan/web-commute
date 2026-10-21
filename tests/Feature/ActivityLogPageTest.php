<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The admin Activity Log page: who may open it, what it shows, how it filters, and the CSV export.
 */
class ActivityLogPageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        foreach (['admin', 'driver_manager', 'maintenance_manager', 'driver', 'commuter'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create()->assignRole($role);
        $user->markEmailAsVerified();

        return $user;
    }

    /** A recognisable entry so assertions never depend on the rest of the dev database. */
    private function entry(string $category, string $event, string $description, array $props = [], ?User $causer = null): Activity
    {
        return Audit::log($category, $event, $description, null, $props, $causer);
    }

    // ───────────────────────── access ─────────────────────────

    public function test_only_an_admin_can_open_the_page_or_export_it()
    {
        foreach (['driver_manager', 'maintenance_manager', 'driver', 'commuter'] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)->get(route('activity-log.index'))->assertForbidden();
            $this->actingAs($user)->get(route('activity-log.export'))->assertForbidden();
        }

        auth()->logout();
        $this->get(route('activity-log.index'))->assertRedirect(route('login'));
    }

    public function test_an_admin_sees_the_page_and_it_is_in_their_menu()
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get(route('activity-log.index'))
            ->assertOk()
            ->assertSee('Activity')
            ->assertSee(route('activity-log.index'), false); // sidebar link
    }

    public function test_other_roles_do_not_get_the_menu_entry()
    {
        $this->actingAs($this->userWithRole('driver_manager'))
            ->get(route('dashboard'))
            ->assertDontSee(route('activity-log.index'), false);
    }

    // ───────────────────────── content ─────────────────────────

    public function test_an_entry_shows_who_what_which_record_and_where_from()
    {
        $admin = $this->userWithRole('admin');
        $actor = $this->userWithRole('maintenance_manager');

        $this->actingAs($actor);
        $subject = User::factory()->create();
        Audit::log(Audit::FLEET, 'updated', 'Updated vehicle ZZZ-9001', $subject, [
            'old' => ['plate_number' => 'ZZ-OLD-PLATE'], 'attributes' => ['plate_number' => 'ZZ-NEW-PLATE'],
        ]);
        auth()->logout();

        $this->actingAs($admin)->get(route('activity-log.index', ['q' => 'ZZZ-9001']))
            ->assertOk()
            ->assertSee('Updated vehicle ZZZ-9001')
            ->assertSee($actor->email)
            ->assertSee('Maintenance Manager')
            ->assertSee('Fleet &amp; maintenance', false)
            ->assertSee('User #'.$subject->id)
            ->assertSee('Plate number')   // the changed field, humanised
            ->assertSee('ZZ-OLD-PLATE')   // before
            ->assertSee('ZZ-NEW-PLATE')   // after
            ->assertSee('127.0.0.1');
    }

    public function test_a_failed_sign_in_is_findable_by_the_email_that_was_tried()
    {
        $this->post(route('users.login'), ['email' => 'target.person@example.com', 'password' => 'nope-nope-nope']);

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('activity-log.index', ['q' => 'target.person@example.com']))
            ->assertOk()
            ->assertSee('Failed sign-in for target.person@example.com')
            ->assertSee('Not signed in');
    }

    public function test_fare_rate_edits_render_as_a_tier_table()
    {
        $this->entry(Audit::FARE, 'fare_rates_updated', 'Updated 1 fare rate(s) ZZTIER', [
            'rows_changed' => 1,
            'changes' => [['km' => 7, 'regular' => ['old' => 20, 'new' => 25.5], 'discount' => ['old' => 16, 'new' => 16]]],
        ]);

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('activity-log.index', ['q' => 'ZZTIER']))
            ->assertOk()
            ->assertSee('7 km')
            ->assertSee('₱20.00 → ₱25.50');
    }

    public function test_legacy_noise_rows_are_hidden_without_deleting_them()
    {
        $noise = activity()->event('Index')->log('Action performed: zzlegacyindex');
        $this->entry(Audit::AUTH, 'login', 'Signed in zzreal@example.com');

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('activity-log.index', ['q' => 'zz']))
            ->assertOk()
            ->assertDontSee('zzlegacyindex')
            ->assertSee('zzreal@example.com');

        $this->assertNotNull(Activity::find($noise->id));
    }

    public function test_a_hostile_value_in_an_entry_is_escaped()
    {
        $this->entry(Audit::ACCOUNT, 'created', 'Created user <script>alert(1)</script>zzxss');

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('activity-log.index', ['q' => 'zzxss']))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_a_secret_stored_by_mistake_is_masked_on_screen()
    {
        $this->entry(Audit::ACCOUNT, 'updated', 'Updated zzmask', [
            'old' => ['password' => 'hunter2-old'], 'attributes' => ['password' => 'hunter2-new', 'api_token' => 'tok-123'],
        ]);

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('activity-log.index', ['q' => 'zzmask']))
            ->assertOk()
            ->assertDontSee('hunter2-old')
            ->assertDontSee('hunter2-new')
            ->assertDontSee('tok-123')
            ->assertSee('••• hidden •••');
    }

    // ───────────────────────── filters ─────────────────────────

    public function test_it_filters_by_category_event_text_and_dates()
    {
        $admin = $this->userWithRole('admin');
        $this->entry(Audit::AUTH, 'login', 'Signed in zzfilter-a@example.com');
        $this->entry(Audit::AUTH, 'logout', 'Signed out zzfilter-a@example.com');
        $this->entry(Audit::PAYMENT, 'created', 'Created payment zzfilter-pay');
        $old = $this->entry(Audit::AUTH, 'login', 'Signed in zzfilter-old@example.com');
        Activity::where('id', $old->id)->update(['created_at' => now()->subDays(30)]);

        $page = fn (array $q) => $this->actingAs($admin)->get(route('activity-log.index', $q + ['q' => 'zzfilter']));

        $page(['category' => 'payment'])->assertSee('zzfilter-pay')->assertDontSee('zzfilter-a@example.com');
        $page(['event' => 'logout'])->assertSee('Signed out zzfilter-a')->assertDontSee('zzfilter-pay')->assertDontSee('Signed in zzfilter-a');
        $page(['from' => now()->subDays(2)->toDateString()])->assertSee('zzfilter-a@example.com')->assertDontSee('zzfilter-old');
        $page(['to' => now()->subDays(10)->toDateString()])->assertSee('zzfilter-old')->assertDontSee('zzfilter-a@example.com');
        $page([])->assertSee('zzfilter-old')->assertSee('zzfilter-pay');
    }

    public function test_the_end_date_cannot_be_before_the_start_date()
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get(route('activity-log.index', ['from' => '2026-05-10', 'to' => '2026-05-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_an_unknown_category_or_event_is_rejected_not_queried()
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get(route('activity-log.index', ['category' => "x' OR 1=1 --", 'event' => 'nope']))
            ->assertSessionHasErrors(['category', 'event']);
    }

    public function test_search_treats_percent_and_underscore_literally()
    {
        $admin = $this->userWithRole('admin');
        $this->entry(Audit::AUTH, 'login', 'Signed in zzwild@example.com');

        $this->actingAs($admin)->get(route('activity-log.index', ['q' => '%']))
            ->assertOk()->assertDontSee('zzwild@example.com');
    }

    public function test_the_list_is_paged_ten_at_a_time_newest_first()
    {
        foreach (range(1, 12) as $i) {
            $this->entry(Audit::AUTH, 'login', sprintf('Signed in zzpage-%02d@example.com', $i));
        }
        $admin = $this->userWithRole('admin');

        $first = $this->actingAs($admin)->get(route('activity-log.index', ['q' => 'zzpage']))->assertOk();
        $first->assertSee('zzpage-12@example.com')->assertSee('zzpage-03@example.com')->assertDontSee('zzpage-02@example.com');
        $this->actingAs($admin)->get(route('activity-log.index', ['q' => 'zzpage', 'page' => 2]))
            ->assertSee('zzpage-02@example.com')->assertSee('zzpage-01@example.com')->assertDontSee('zzpage-12@example.com');
    }

    public function test_an_empty_result_says_so()
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get(route('activity-log.index', ['q' => 'nothing-will-ever-match-this-zzz']))
            ->assertOk()
            ->assertSee('No entries match your filters');
    }

    // ───────────────────────── export ─────────────────────────

    public function test_the_export_is_a_csv_of_the_filtered_entries_and_is_itself_audited()
    {
        $admin = $this->userWithRole('admin');
        $this->entry(Audit::AUTH, 'login', 'Signed in zzexport-1@example.com');
        $this->entry(Audit::AUTH, 'login', 'Signed in zzexport-2@example.com');
        $this->entry(Audit::PAYMENT, 'created', 'Created payment zzexport-other');

        $response = $this->actingAs($admin)->get(route('activity-log.export', ['q' => 'zzexport', 'category' => 'auth']));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('smartcommute_activity_log_', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Entry,Time,Category,Event', $csv);
        $this->assertStringContainsString('zzexport-1@example.com', $csv);
        $this->assertStringContainsString('zzexport-2@example.com', $csv);
        $this->assertStringNotContainsString('zzexport-other', $csv, 'the filters apply to the export too');

        $logged = Activity::where('event', 'activity_log_exported')->latest('id')->first();
        $this->assertNotNull($logged, 'an export must leave a trace');
        $this->assertSame($admin->id, $logged->causer_id);
        $this->assertSame(2, $logged->properties['rows']);
        $this->assertSame('auth', $logged->properties['filters']['category']);
    }

    public function test_a_spreadsheet_formula_in_an_entry_is_neutralised_in_the_export()
    {
        $this->entry(Audit::ACCOUNT, 'created', '=HYPERLINK("http://evil.example","click") zzformula');

        $csv = $this->actingAs($this->userWithRole('admin'))
            ->get(route('activity-log.export', ['q' => 'zzformula']))
            ->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
        $this->assertStringNotContainsString('"=HYPERLINK', $csv);
    }

    public function test_exporting_a_report_is_audited_with_who_what_and_the_range()
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('reports.export', ['type' => 'financial', 'start_date' => '2026-01-01', 'end_date' => '2026-01-31']))
            ->assertOk();

        $logged = Activity::where('event', 'report_exported')->latest('id')->first();
        $this->assertNotNull($logged);
        $this->assertSame($admin->id, $logged->causer_id);
        $this->assertSame('system', $logged->log_name);
        $this->assertSame('financial', $logged->properties['report']);
        $this->assertSame('2026-01-01', $logged->properties['from']);
    }

    public function test_viewing_the_page_writes_nothing()
    {
        $admin = $this->userWithRole('admin');
        $before = Activity::count();

        $this->actingAs($admin)->get(route('activity-log.index'))->assertOk();
        $this->actingAs($admin)->get(route('activity-log.index', ['q' => 'x']))->assertOk();

        $this->assertSame($before, Activity::count(), 'reading the audit trail must not write to it');
    }
}
