<?php

namespace Tests\Unit;

use App\Support\ServiceCorridor;
use Tests\TestCase;

/**
 * The Minglanilla -> Cebu IT Park road is a fixed, system-scope corridor
 * (not admin-editable); markers may only sit on it.
 */
class ServiceCorridorTest extends TestCase
{
    public function test_the_corridor_file_is_a_static_linestring_between_taminasca_and_it_park()
    {
        $geo = json_decode(file_get_contents(resource_path('geo/minglanilla-itpark-corridor.geojson')), true);

        $line = $geo['features'][0]['geometry'];
        $this->assertSame('LineString', $line['type']);
        $this->assertGreaterThan(100, count($line['coordinates']));

        [$lng, $lat] = $line['coordinates'][0];
        $this->assertEqualsWithDelta(10.2417, $lat, 0.001);  // Taminasca PUJ, Abuno, Minglanilla
        $this->assertEqualsWithDelta(123.7849, $lng, 0.001);

        [$lng, $lat] = end($line['coordinates']);
        $this->assertEqualsWithDelta(10.3277, $lat, 0.01);   // Cebu IT Park
        $this->assertEqualsWithDelta(123.9055, $lng, 0.01);
    }

    public function test_a_point_on_the_road_is_on_the_corridor()
    {
        $snap = ServiceCorridor::snap(10.284729, 123.859999);

        $this->assertNotNull($snap);
        $this->assertLessThan(1, $snap['distance']);
    }

    public function test_a_point_a_little_off_the_road_snaps_onto_it()
    {
        $snap = ServiceCorridor::snap(10.284729, 123.860270); // ~30 m away

        $this->assertNotNull($snap);
        $this->assertLessThan(1, ServiceCorridor::nearest($snap['lat'], $snap['lng'])['distance']);
    }

    public function test_points_far_from_the_road_are_rejected()
    {
        $this->assertNull(ServiceCorridor::snap(14.5995, 120.9842));  // Manila
        $this->assertNull(ServiceCorridor::snap(10.284729, 123.8650)); // ~550 m off the road
    }

    public function test_every_stop_sits_on_the_corridor()
    {
        foreach (ServiceCorridor::stops() as $stop) {
            $this->assertLessThan(1, ServiceCorridor::nearest($stop['lat'], $stop['lng'])['distance'], $stop['name']);
        }
    }
}
