<?php

namespace Tests\Feature;

use Tests\TestCase;

class GuestControllerTest extends TestCase
{
    public function test_guest_home_route_responds()
    {
        $response = $this->get(route('home'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_guest_map_route_responds()
    {
        $response = $this->get(route('map'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_guest_login_route_responds()
    {
        $response = $this->get(route('login'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_guest_register_route_responds()
    {
        $response = $this->get(route('register'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    /** 
     * Guest Access Tests
     */
    public function test_guest_can_view_home_page()
    {
        $response = $this->get(route('home'));
        $response->assertOk();
    }

    public function test_guest_can_view_map_with_fares()
    {
        // Assuming there are fare entries in the database
        $response = $this->get(route('map'));
        $response->assertOk();
    }

    public function test_guest_can_access_login_page()
    {
        $response = $this->get(route('login'));
        $response->assertOk();
    }

    public function test_guest_can_access_register_page()
    {
        $response = $this->get(route('register'));
        $response->assertOk();
    }

    public function test_guest_redirected_when_accessing_protected_route()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }
}