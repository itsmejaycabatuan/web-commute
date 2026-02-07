<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ViewsTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_landing_page_can_be_rendered() {
        $response = $this->get(route('home'));

        $response->assertOk();
    }

    public function test_dashboard_page_can_be_rendered() {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('commuter.dashboard'));
        
        $response->assertOk();
    }

    public function test_user_profile_page_can_be_rendered() {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('commuter.commuter'));

        $response->assertOk();
    }
}
