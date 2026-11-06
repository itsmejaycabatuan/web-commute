<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The font-size setting is a CSS zoom. Unchecked, it squeezed layouts on narrow screens (search boxes collapsing,
 * buttons clipped) because Tailwind's breakpoints see the real screen width while zoom shrinks the room the page has.
 * partials/font-scale limits the zoom actually applied; every page must go through it.
 */
class FontScaleTest extends TestCase
{
    use DatabaseTransactions;

    private function user(string $role): User
    {
        Role::firstOrCreate(['name' => $role]);
        $user = User::factory()->create(['email_verified_at' => now()])->assignRole($role);
        Wallet::firstOrCreate(['user_id' => $user->id]);

        return $user;
    }

    public function test_staff_and_commuter_pages_load_the_width_limited_zoom_instead_of_setting_it_directly()
    {
        foreach (['admin' => 'dashboard', 'commuter' => 'payment.topup'] as $role => $route) {
            $html = $this->actingAs($this->user($role))->get(route($route))->assertOk()->getContent();

            $this->assertStringContainsString('window.scApplyFontZoom', $html, "{$role} page defines the limiter");
            $this->assertStringContainsString('scApplyFontZoom(currentSize)', $html, "{$role} page applies the saved size through it");
            $this->assertStringNotContainsString('fontZoomLevels', $html, "{$role} page has no second copy of the zoom table");
        }
    }

    public function test_the_limiter_keeps_the_layout_inside_its_breakpoint_range_and_a_320px_phone()
    {
        $partial = file_get_contents(resource_path('views/partials/font-scale.blade.php'));

        // Same ranges Tailwind uses, widest first, and the phone floor.
        $this->assertStringContainsString('var RANGES = [1280, 1024, 768, 640];', $partial);
        $this->assertStringContainsString('var PHONE_MIN = 320;', $partial);
        $this->assertStringContainsString('var TOLERANCE = 0.9;', $partial);
        $this->assertStringContainsString('range ? range * TOLERANCE : PHONE_MIN', $partial);
        // The zoom is the chosen one capped by width ÷ the range's lower edge, never below 1 once enlarged.
        $this->assertStringContainsString('Math.max(1, Math.min(requested, w / floor))', $partial);
        // Smaller text is never limited, and the zoom is re-applied when the window changes size.
        $this->assertStringContainsString('if (requested <= 1)', $partial);
        $this->assertStringContainsString("window.addEventListener('resize'", $partial);
    }

    public function test_no_view_sets_the_zoom_from_a_fixed_table_any_more()
    {
        $offenders = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.php') && str_contains(file_get_contents($file->getPathname()), 'fontZoomLevels')) {
                $offenders[] = str_replace(resource_path('views').'/', '', $file->getPathname());
            }
        }

        $this->assertSame([], $offenders, 'font zoom goes through partials/font-scale');
    }

    public function test_the_settings_pages_explain_a_size_that_had_to_be_limited()
    {
        foreach (['admin', 'commuter'] as $role) {
            $html = $this->actingAs($this->user($role))->get(route('settings.edit'))->assertOk()->getContent();

            $this->assertStringContainsString('id="font-scale-note"', $html, "{$role} settings has the note");
            $this->assertStringContainsString("addEventListener('sc:fontzoom'", $html);
            $this->assertStringContainsString('scApplyFontZoom(size)', $html, "{$role} settings applies a chosen size through the limiter");
        }
    }

    public function test_every_view_that_looks_up_the_font_size_tables_declares_them()
    {
        // Removing the old zoom table once also removed intToLabel / sizeToDb from the staff Settings page. They were
        // still used further down, the script died with a ReferenceError before registering the page's Alpine data,
        // and every tab rendered blank. A view that indexes one of these tables must declare it itself.
        $offenders = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            foreach (['intToLabel', 'sizeToDb'] as $table) {
                if (str_contains($source, $table.'[') && ! preg_match('/\b(var|let|const)\s+'.$table.'\s*=/', $source)) {
                    $offenders[] = str_replace(resource_path('views').'/', '', $file->getPathname())." uses {$table} without declaring it";
                }
            }
        }

        $this->assertSame([], $offenders);
    }

    public function test_the_staff_settings_page_declares_its_script_globals_and_alpine_data()
    {
        $html = $this->actingAs($this->user('admin'))->get(route('settings.edit'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/var\s+intToLabel\s*=\s*\{[^}]*13:\s*\'xlarge\'/s', $html);
        $this->assertMatchesRegularExpression('/var\s+sizeToDb\s*=\s*\{[^}]*xlarge:\s*13/s', $html);
        $this->assertStringContainsString("Alpine.data('settingsPage'", $html);
    }

    public function test_the_sidebar_tightens_on_short_screens_and_keeps_the_current_page_in_view()
    {
        // Usable height = screen height ÷ zoom. Under ~780px the fixed footer would squeeze the nav list to a few
        // half-cut rows at large text, so the limiter flags the page and the sidebar switches to compact rows.
        $partial = file_get_contents(resource_path('views/partials/font-scale.blade.php'));
        $this->assertStringContainsString("classList.toggle('sc-short', window.innerHeight / applied < 780)", $partial);

        $sidebar = file_get_contents(resource_path('views/components/layout/sidebar.blade.php'));
        foreach (['.sc-nav-link', '.sc-nav-ico', '.sc-nav .sc-nav-foot', '.sc-user', '.sc-act'] as $selector) {
            $this->assertStringContainsString("html.sc-short {$selector} {", $sidebar, "compact rule for {$selector}");
        }
        // The list scrolls to the active page (and again once fonts/zoom have settled), never the page itself.
        $this->assertStringContainsString("active.scrollIntoView({ block: 'nearest' })", $sidebar);
        $this->assertStringContainsString("addEventListener('sc:fontzoom'", $sidebar);
    }

    public function test_widths_taken_from_the_viewport_divide_out_the_text_zoom()
    {
        // vw units ignore CSS zoom, so a "100vw - 24px" banner grew to ~1.2x the screen at large text.
        $partial = file_get_contents(resource_path('views/partials/font-scale.blade.php'));
        $this->assertStringContainsString("style.setProperty('--sc-zoom', applied)", $partial);

        foreach (['map.blade.php', 'components/toast.blade.php'] as $view) {
            $source = file_get_contents(resource_path("views/{$view}"));
            $this->assertDoesNotMatchRegularExpression('/(?<!\()100vw(?! \/ var\(--sc-zoom)/', $source, "{$view} sizes from 100vw without the zoom");
        }
        $this->assertStringContainsString('max-w-[calc(86vw/var(--sc-zoom,1))]', file_get_contents(resource_path('views/components/layout/sidebar.blade.php')));
    }

    public function test_filter_rows_wrap_instead_of_running_off_a_phone_screen()
    {
        $timeKeeping = file_get_contents(resource_path('views/driver-manager/time-keeping.blade.php'));
        $this->assertStringContainsString('<div class="flex flex-wrap items-center justify-between gap-2">', $timeKeeping);

        // The driver details cards are evaluated before a driver is picked.
        $dashboard = file_get_contents(resource_path('views/driver-manager/dashboard.blade.php'));
        $this->assertStringNotContainsString('x-text="selectedDriver.', $dashboard);

        $commuters = file_get_contents(resource_path('views/admin/commuters/index.blade.php'));
        $this->assertStringContainsString('<div class="flex flex-wrap items-center justify-between gap-2">', $commuters);
        $this->assertStringContainsString('<div class="flex flex-wrap items-center gap-1.5">', $commuters);

        // The scan loops are evaluated before any scan exists; they must not dereference a null scan.
        $vehicles = file_get_contents(resource_path('views/maintenance-manager/vehicles.blade.php'));
        $this->assertStringNotContainsString('in scan.pages', $vehicles);
        $this->assertStringNotContainsString('in scan.warnings', $vehicles);
    }

    public function test_the_sidebar_opens_by_the_effective_width_not_the_screen_width()
    {
        // At 125 % a 1366 px screen has only 1093 px to lay out in; opening the wide sidebar there left ~590 px for
        // the page (filters and buttons cut off). It starts as the slim rail until the layout itself reaches 1100 px.
        $head = file_get_contents(resource_path('views/partials/head-scripts.blade.php'));

        $this->assertStringContainsString("open: (window.innerWidth / (parseFloat(document.documentElement.style.zoom) || 1)) >= 1100,", $head);
        $this->assertStringNotContainsString('open: window.innerWidth >= 1100', $head);
    }

    /** vh is not scaled by CSS zoom, so a "90vh" modal grows to 90vh x zoom and spills off the screen at large text. */
    public function test_modal_heights_taken_from_the_viewport_divide_out_the_text_zoom()
    {
        $offenders = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            // license-embed is a bare iframe page that never loads the zoom, so its 100vh is already correct.
            if (! str_ends_with($file->getFilename(), '.blade.php') || $file->getFilename() === 'license-embed.blade.php') {
                continue;
            }

            foreach (file($file->getPathname()) as $n => $line) {
                if (preg_match('/max-h-\[\d+vh\]|max-height:\s*\d+vh/', $line)) {
                    $offenders[] = str_replace(resource_path('views').'/', '', $file->getPathname()).':'.($n + 1);
                }
            }
        }

        $this->assertSame([], $offenders, 'Divide modal heights by var(--sc-zoom,1), e.g. max-h-[calc(90vh/var(--sc-zoom,1))]');
    }
}
