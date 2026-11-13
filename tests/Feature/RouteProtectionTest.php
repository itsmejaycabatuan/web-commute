<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Which pages are protected, and from whom.
 *
 * The first test walks every registered web route: unless a route is on the
 * public allow-list below it must turn a guest away. A new route that forgets
 * its middleware fails here instead of shipping open.
 */
class RouteProtectionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Routes a guest may reach on purpose (landing, auth screens, the PayMongo
     * webhook which is signature-checked, and the public live map data).
     */
    private const PUBLIC_ROUTE_NAMES = [
        'home', 'login', 'register', 'users.login', 'users.register',
        'driver.register.page', 'driver.register',
        'password.request', 'password.email', 'password.reset', 'password.update',
        'map.guest', 'paymongo.webhook',
    ];

    /** Public by design but unnamed, or answering guests with their own JSON 403. */
    private const PUBLIC_URIS = [
        'track/vehicles/active', 'track/vehicle/broadcast', 'sanctum/csrf-cookie',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'commuter', 'driver', 'driver_manager', 'maintenance_manager'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    /** @return array<int, LaravelRoute> */
    private function webRoutes(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route) => in_array('web', $route->gatherMiddleware(), true))
            ->reject(fn (LaravelRoute $route) => str_starts_with($route->uri(), '_') || str_starts_with($route->uri(), 'api/'))
            ->values()
            ->all();
    }

    private function concreteUri(LaravelRoute $route): string
    {
        return '/'.ltrim(preg_replace('/\{[^}]+\}/', '1', $route->uri()), '/');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()])->assignRole($role);

        if ($role === 'driver') {
            Driver::factory()->create(['user_id' => $user->id, 'is_approved' => 1, 'is_rejected' => 0]);
        }

        return $user;
    }

    public function test_every_route_turns_a_guest_away_unless_it_is_public_on_purpose()
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $open = [];
        $checked = 0;

        foreach ($this->webRoutes() as $route) {
            if (in_array($route->getName(), self::PUBLIC_ROUTE_NAMES, true)
                || in_array($route->uri(), self::PUBLIC_URIS, true)
                || $route->uri() === '/') {
                continue;
            }

            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $status = $this->call($method, $this->concreteUri($route))->getStatusCode();
                $checked++;

                if ($status < 300 || $status >= 500) {
                    $open[] = "{$method} /{$route->uri()} answered {$status}";
                }
            }
        }

        $this->assertGreaterThan(60, $checked, 'The route sweep should have covered the whole application.');
        $this->assertSame([], $open, "Routes reachable without signing in:\n".implode("\n", $open));
    }

    /**
     * Back after logging out redraws the old page from the browser's history unless the page said `no-store`,
     * so a signed-in user's pages must say it. Guest pages and pages with their own caching stay as they were.
     */
    public function test_pages_served_to_a_signed_in_user_are_never_stored_by_the_browser()
    {
        $user = $this->userWithRole('commuter');

        foreach (['/map', '/payment/history', '/profile'] as $uri) {
            $response = $this->actingAs($user)->get($uri)->assertOk();

            $this->assertTrue($response->headers->hasCacheControlDirective('no-store'), "{$uri} is cacheable by the browser");
        }
    }

    public function test_logged_out_pages_and_responses_with_their_own_caching_are_left_alone()
    {
        $this->get('/login')->assertOk()->assertHeaderMissing('Pragma');
        $this->assertFalse($this->get('/login')->headers->hasCacheControlDirective('no-store'));

        Route::middleware('web')->get('/_cache-probe', fn () => response('x')->header('Cache-Control', 'private, max-age=3600'));

        $response = $this->actingAs($this->userWithRole('commuter'))->get('/_cache-probe')->assertOk();

        $this->assertTrue($response->headers->hasCacheControlDirective('max-age'));
        $this->assertFalse($response->headers->hasCacheControlDirective('no-store'));
    }

    /** @dataProvider adminOnlyRoutes */
    public function test_admin_only_pages_refuse_every_other_role(string $method, string $uri)
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        foreach (['commuter', 'driver', 'driver_manager', 'maintenance_manager'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->call($method, $uri)
                ->assertForbidden();
        }
    }

    public static function adminOnlyRoutes(): array
    {
        return [
            'commuters' => ['GET', '/commuters'],
            'staff list' => ['GET', '/staff'],
            'staff create' => ['POST', '/staff'],
            'fares' => ['GET', '/fares'],
            'reports' => ['GET', '/reports'],
            'fare transactions' => ['GET', '/transactions'],
            'wallet top-ups' => ['GET', '/topups'],
        ];
    }

    public function test_staff_pages_are_split_between_the_staff_roles()
    {
        $driverManager = $this->userWithRole('driver_manager');
        $maintenanceManager = $this->userWithRole('maintenance_manager');

        $this->actingAs($driverManager)->get('/violations-log')->assertOk();
        $this->actingAs($driverManager)->get('/preventive-maintenance')->assertForbidden();
        $this->actingAs($driverManager)->get('/vehicles')->assertForbidden();

        $this->actingAs($maintenanceManager)->get('/preventive-maintenance')->assertOk();
        $this->actingAs($maintenanceManager)->get('/vehicles')->assertOk();
        $this->actingAs($maintenanceManager)->get('/violations-log')->assertForbidden();
        $this->actingAs($maintenanceManager)->get('/drivers')->assertForbidden();
    }

    public function test_the_admin_may_open_the_vehicle_page()
    {
        $this->actingAs($this->userWithRole('admin'))->get('/vehicles')->assertOk();

        foreach (['commuter', 'driver', 'driver_manager'] as $role) {
            $this->actingAs($this->userWithRole($role))->get('/vehicles')->assertForbidden();
        }
    }

    public function test_driver_pages_are_closed_to_everyone_but_drivers()
    {
        foreach (['commuter', 'admin', 'driver_manager', 'maintenance_manager'] as $role) {
            $this->actingAs($this->userWithRole($role))->get('/timekeeping')->assertForbidden();
            $this->actingAs($this->userWithRole($role))->get('/violations')->assertForbidden();
        }

        $this->actingAs($this->userWithRole('driver'))->get('/timekeeping')->assertOk();
    }

    public function test_the_old_maintenance_log_url_does_not_exist_and_the_new_one_is_maintenance_manager_only()
    {
        $manager = $this->userWithRole('maintenance_manager');

        $this->actingAs($manager)->get('/vehicle-maintenance-log')->assertNotFound();
        $this->actingAs($manager)->get('/maintenance-logs')->assertOk();
        $this->actingAs($this->userWithRole('admin'))->get('/maintenance-logs')->assertForbidden();
    }
}
