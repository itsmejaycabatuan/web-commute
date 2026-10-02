<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression tests for the mobile/landscape UI fixes:
 *
 *  1. Flash messages must render on the topmost layer (z-[9999]).
 *  2. The driver must get BOTH mobile sidebar sheets on the map page - the
 *     left FAB used to be limited to guests/commuters, which made the
 *     driver's left sidebar (duty status + timekeeping) unreachable on
 *     phones while the right sheet kept working.
 *  3. Short/landscape viewports need their own layout: every console view
 *     ships the `is-landscape` styles and the dashboards opt into
 *     `.landscape-split`; the mobile nav/drawer expose their `bar-*` /
 *     `drawer-nav-link` hooks for the compact landscape chrome.
 */
class MobileLayoutTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['driver', 'admin', 'commuter'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    protected function actAsDriver(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()])->assignRole('driver');

        Driver::factory()->create([
            'user_id' => $user->id,
            'status' => 'inactive',
            'is_approved' => 1,
            'is_rejected' => 0,
        ]);

        return $user;
    }

    /** 1. Flash messages live above the nav bar, drawer, sheets and modals. */
    public function test_flash_message_renders_on_the_topmost_layer()
    {
        session()->flash('success', 'Saved!');

        $html = view('components.flash', ['errors' => new ViewErrorBag])->render();

        $this->assertStringContainsString('data-flash-message', $html);
        $this->assertStringContainsString('z-[9999]', $html);
        $this->assertStringNotContainsString('id="flash-message"', $html, 'duplicate ids are invalid HTML');
    }

    /** 2. Driver map: both sidebar FABs and the left panel exist. */
    public function test_driver_map_exposes_both_mobile_sidebars()
    {
        $html = $this->actingAs($this->actAsDriver())->get(route('map'))->assertOk()->getContent();

        $this->assertStringContainsString('class="mobile-fab-wrap mobile-fab-wrap-left', $html);
        $this->assertStringContainsString("openMobileSidebar('left')", $html);
        $this->assertStringContainsString('class="mobile-fab-wrap mobile-fab-wrap-right', $html);
        $this->assertStringContainsString("openMobileSidebar('right')", $html);
        $this->assertStringContainsString('id="left-sidebar-form"', $html);
    }

    public function test_commuter_map_exposes_both_mobile_sidebars()
    {
        $user = User::factory()->create(['email_verified_at' => now()])->assignRole('commuter');

        $html = $this->actingAs($user)->get(route('map'))->assertOk()->getContent();

        $this->assertStringContainsString('class="mobile-fab-wrap mobile-fab-wrap-left', $html);
        $this->assertStringContainsString('class="mobile-fab-wrap mobile-fab-wrap-right', $html);
    }

    public function test_admin_map_has_no_mobile_sidebar_fabs()
    {
        $user = User::factory()->create(['email_verified_at' => now()])->assignRole('admin');

        $html = $this->actingAs($user)->get(route('map'))->assertOk()->getContent();

        $this->assertStringNotContainsString('class="mobile-fab-wrap mobile-fab-wrap-left', $html);
        $this->assertStringNotContainsString('class="mobile-fab-wrap mobile-fab-wrap-right', $html);
    }

    /** Moving a panel into a sheet must not rewrite its desktop classes. */
    public function test_mobile_sheets_restore_the_original_panel_classes()
    {
        $html = $this->actingAs($this->actAsDriver())->get(route('map'))->assertOk()->getContent();

        $this->assertStringContainsString('stashDesktopClasses', $html);
        $this->assertStringContainsString('restoreDesktopClasses', $html);
        $this->assertStringNotContainsString('LEFT_DESKTOP_CLASSES', $html);
        $this->assertStringNotContainsString('RIGHT_DESKTOP_CLASSES', $html);
    }

    /** 3. Landscape support is wired into every console view. */
    public function test_driver_dashboard_has_landscape_split_and_mobile_chrome()
    {
        $html = $this->actingAs($this->actAsDriver())->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('(orientation: landscape)', $html, 'landscape media query missing');
        $this->assertStringContainsString('is-landscape', $html);
        $this->assertStringContainsString('landscape-split', $html);
        $this->assertStringContainsString('id="mobile-bottom-bar"', $html);
        $this->assertStringContainsString('bar-inner', $html);
        $this->assertStringContainsString('drawer-nav-link', $html);
    }

    public function test_map_view_ships_landscape_styles_for_sheets_and_fabs()
    {
        $html = $this->actingAs($this->actAsDriver())->get(route('map'))->assertOk()->getContent();

        $this->assertStringContainsString('is-landscape', $html);
        $this->assertStringContainsString('html.is-landscape .mobile-sheet', $html);
        $this->assertStringContainsString('html.is-landscape .mobile-fab-wrap-left', $html);
    }

    /** The desktop right sidebar must scroll instead of running off-screen. */
    public function test_right_sidebar_scrolls()
    {
        $html = $this->actingAs($this->actAsDriver())->get(route('map'))->assertOk()->getContent();

        preg_match('/id="right-sidebar-content"\s+class="([^"]+)"/', $html, $m);
        $this->assertNotEmpty($m, 'right sidebar not found');

        $classes = $m[1];
        $this->assertStringContainsString('overflow-y-auto', $classes);
        $this->assertStringContainsString('overscroll-contain', $classes);
        $this->assertStringContainsString('custom-scroll', $classes);
        $this->assertStringContainsString('max-h-[calc(100vh-120px)]', $classes);
    }
}
