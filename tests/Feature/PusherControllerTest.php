<?php

namespace Tests\Feature;

use App\Events\PusherEvent;
use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The Pusher test page and its fire-event endpoint are development tooling:
 * signed-in admins on a local install only.
 */
class PusherControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin']);

        return User::factory()->create(['email_verified_at' => now()])->assignRole('admin');
    }

    protected function asLocal(): void
    {
        $this->app['env'] = 'local';
    }

    public function test_guests_cannot_open_the_pusher_page_or_fire_events()
    {
        $this->asLocal();

        $this->get(route('pusher.index'))->assertRedirect(route('login'));

        $this->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('fire.event'))
            ->assertRedirect(route('login'));
    }

    public function test_the_tooling_does_not_exist_outside_a_local_install()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('pusher.index'))->assertNotFound();

        $this->actingAs($admin)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('fire.event'))
            ->assertNotFound();
    }

    public function test_an_admin_on_a_local_install_can_fire_an_event()
    {
        Event::fake([PusherEvent::class]);
        $this->asLocal();

        $this->actingAs($this->admin())
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('fire.event'))
            ->assertOk()
            ->assertJson(['message' => 'event sent']);

        Event::assertDispatched(PusherEvent::class);
    }

    public function test_non_admins_cannot_fire_events()
    {
        $this->asLocal();
        Role::firstOrCreate(['name' => 'commuter']);
        $commuter = User::factory()->create(['email_verified_at' => now()])->assignRole('commuter');

        $this->actingAs($commuter)
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->post(route('fire.event'))
            ->assertForbidden();
    }
}
