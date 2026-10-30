<?php

namespace Tests\Feature;

use App\Models\DevMarker;
use App\Models\User;
use App\Support\ServiceCorridor;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Dev markers are a local-only admin tool: they must not be reachable by
 * guests, by other roles, or on a non-local install.
 */
class DevMarkerControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function userWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role]);

        return User::factory()->create(['email_verified_at' => now()])->assignRole($role);
    }

    public function test_guests_are_sent_to_the_login_page()
    {
        $this->app['env'] = 'local';

        $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('driver.dev.add-marker'), ['lat' => 10.284729, 'lng' => 123.859999])
            ->assertRedirect(route('login'));

        $this->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('driver.dev.clear-markers'))
            ->assertRedirect(route('login'));
    }

    public function test_only_admins_may_use_the_markers()
    {
        $this->app['env'] = 'local';

        $this->actingAs($this->userWithRole('commuter'))
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('driver.dev.add-marker'), ['lat' => 10.284729, 'lng' => 123.859999])
            ->assertForbidden();
    }

    public function test_the_markers_do_not_exist_outside_a_local_install()
    {
        $this->actingAs($this->userWithRole('admin'))
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('driver.dev.add-marker'), ['lat' => 10.284729, 'lng' => 123.859999])
            ->assertNotFound();
    }

    public function test_an_admin_can_add_toggle_and_remove_their_markers_locally()
    {
        $this->app['env'] = 'local';
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('driver.dev.add-marker'), ['lat' => 10.284729, 'lng' => 123.859999])
            ->assertSessionHas('success');

        $marker = DevMarker::where('user_id', $admin->id)->firstOrFail();

        $this->actingAs($admin)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('driver.dev.toggle-marker', ['marker' => $marker->id]));
        $this->assertSame('inactive', $marker->fresh()->status);

        $this->actingAs($admin)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->delete(route('driver.dev.remove-marker', ['marker' => $marker->id]));
        $this->assertDatabaseMissing('dev_markers', ['id' => $marker->id]);
    }

    public function test_markers_off_the_minglanilla_it_park_road_are_rejected()
    {
        $this->app['env'] = 'local';
        $admin = $this->userWithRole('admin');

        // Manila - nowhere near the fixed corridor.
        $this->actingAs($admin)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('driver.dev.add-marker'), ['lat' => 14.5995, 'lng' => 120.9842])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('dev_markers', ['user_id' => $admin->id]);
    }

    public function test_markers_near_the_road_are_snapped_onto_it()
    {
        $this->app['env'] = 'local';
        $admin = $this->userWithRole('admin');

        // ~30 m east of a point on the corridor.
        $this->actingAs($admin)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('driver.dev.add-marker'), ['lat' => 10.284729, 'lng' => 123.860270])
            ->assertSessionHas('success');

        $marker = DevMarker::where('user_id', $admin->id)->firstOrFail();
        $this->assertLessThan(1, ServiceCorridor::nearest((float) $marker->lat, (float) $marker->lng)['distance']);
    }
}
