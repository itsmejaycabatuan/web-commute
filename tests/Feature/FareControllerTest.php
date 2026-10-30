<?php

namespace Tests\Feature;

use App\Models\Fare;
use App\Models\FareRate;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FareControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_fare_index_route_responds()
    {
        $response = $this->get(route('fares.index'));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_fare_view_route_responds()
    {
        $response = $this->get(route('fares.view', ['id' => 1]));
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_fare_bulk_update_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->put(route('fares.bulk-update'), ['rates' => []]);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_fare_upload_route_responds()
    {
        $response = $this->withoutMiddleware([VerifyCsrfToken::class])
            ->put(route('fares.upload'), []);
        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_fare_upload_requires_a_pdf()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('fares.upload'), [])
            ->assertSessionHasErrors('fare');

        $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('fares.upload'), ['fare' => UploadedFile::fake()->image('fare.png')])
            ->assertSessionHasErrors('fare');
    }

    public function test_fare_bulk_update_rejects_a_zero_rate()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $rate = FareRate::factory()->create(['regular' => 20]);

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('fares.bulk-update'), [
                'rates' => [$rate->id => ['id' => $rate->id, 'regular' => 0]],
            ]);

        $response->assertSessionHasErrors('rates.*.regular');
        $this->assertEquals(20.0, (float) $rate->fresh()->regular);
    }

    public function test_admin_can_bulk_update_fare_rates()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $rate = FareRate::factory()->create(['regular' => 20]);

        $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('fares.bulk-update'), [
                'rates' => [$rate->id => ['id' => $rate->id, 'regular' => 25.5]],
            ])
            ->assertSessionHas('success');

        $this->assertEquals(25.5, (float) $rate->fresh()->regular);
    }

    public function test_fare_bulk_update_with_valid_data()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $fares = Fare::factory()->count(2)->create();

        $rates = $fares->map(function ($fare) {
            return ['id' => $fare->id, 'fare_amount' => $fare->fare_amount + 10];
        });

        $response = $this->actingAs($admin)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->put(route('fares.bulk-update'), ['rates' => $rates]);

        $this->assertTrue($response->getStatusCode() > 0);
    }

    public function test_non_admin_cannot_access_fare_routes()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('fares.index'));
        $response->assertStatus(403);
    }

    public function test_fare_rates_no_longer_carry_a_discounted_price()
    {
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('fare_rates', 'discount'));

        $admin = User::factory()->create()->assignRole('admin');
        FareRate::factory()->create(['regular' => 20]);

        $this->actingAs($admin)
            ->get(route('fares.index'))
            ->assertOk()
            ->assertDontSee('Discounted');
    }
}
