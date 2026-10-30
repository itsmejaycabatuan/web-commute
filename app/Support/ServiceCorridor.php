<?php

namespace App\Support;

/**
 * The fixed road corridor (Minglanilla -> Cebu IT Park) that markers may be
 * placed on. It is static by design - it comes from the system's scope, not
 * from admin input - so the geometry lives in resources/geo and is never
 * written to the database.
 *
 * The same geometry and tolerance are used by the map (client-side snapping)
 * and by the server (validation), so the two can't drift apart.
 */
class ServiceCorridor
{
    /** How far (metres) from the road line a point may be and still count as "on the road". */
    public const TOLERANCE_METERS = 60;

    /**
     * How far (metres) a commuter's own GPS position may be from the road and
     * still count as "on the route" - wider than the marker tolerance because
     * riders wait on side roads and GPS drifts.
     */
    public const USER_TOLERANCE_METERS = 150;

    private const EARTH_RADIUS_M = 6371008.8;

    private static ?array $geojson = null;

    public static function geojson(): array
    {
        return self::$geojson ??= json_decode(
            file_get_contents(resource_path('geo/minglanilla-itpark-corridor.geojson')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * The fixed stops along the corridor, in travel order (Minglanilla -> IT Park).
     * Every point is a vertex of the corridor line, so a stop is always on the road.
     *
     * @return list<array{name: string, area: string, lat: float, lng: float}>
     */
    public static function stops(): array
    {
        return [
            ['name' => 'Taminasca PUJ', 'area' => 'Abuno, Minglanilla', 'lat' => 10.241726, 'lng' => 123.784859],
            ['name' => 'Pakigne', 'area' => 'Minglanilla', 'lat' => 10.250476, 'lng' => 123.804664],
            ['name' => 'Linao', 'area' => 'Talisay City', 'lat' => 10.256644, 'lng' => 123.814081],
            ['name' => 'Lawaan', 'area' => 'Talisay City', 'lat' => 10.258649, 'lng' => 123.82472],
            ['name' => 'Tabunok', 'area' => 'Talisay City', 'lat' => 10.26139, 'lng' => 123.836102],
            ['name' => 'Bulacao', 'area' => 'Talisay City', 'lat' => 10.267778, 'lng' => 123.843362],
            ['name' => 'Pardo', 'area' => 'Cebu City', 'lat' => 10.283087, 'lng' => 123.858591],
            ['name' => 'Basak Pardo', 'area' => 'Cebu City', 'lat' => 10.289021, 'lng' => 123.867399],
            ['name' => 'Mambaling', 'area' => 'Cebu City', 'lat' => 10.291576, 'lng' => 123.878032],
            ['name' => 'Calamba', 'area' => 'Cebu City', 'lat' => 10.297066, 'lng' => 123.887233],
            ['name' => 'Carreta', 'area' => 'Cebu City', 'lat' => 10.306813, 'lng' => 123.904717],
            ['name' => 'Camputhaw', 'area' => 'Cebu City', 'lat' => 10.316409, 'lng' => 123.90202],
            ['name' => 'Cebu IT Park', 'area' => 'Lahug, Cebu City', 'lat' => 10.327749, 'lng' => 123.905675],
        ];
    }

    /**
     * Closest point on the corridor to the given position.
     *
     * @return array{lat: float, lng: float, distance: float} distance in metres
     */
    public static function nearest(float $lat, float $lng): array
    {
        $best = ['lat' => $lat, 'lng' => $lng, 'distance' => INF];
        $refLat = deg2rad($lat);
        $mPerDegLat = self::EARTH_RADIUS_M * M_PI / 180;
        $mPerDegLng = $mPerDegLat * cos($refLat);

        foreach (self::geojson()['features'] as $feature) {
            $line = $feature['geometry']['coordinates'];

            for ($i = 0, $n = count($line) - 1; $i < $n; $i++) {
                // Local flat projection (metres) around the query point - accurate
                // to well under a metre over the few hundred metres that matter here.
                $ax = ($line[$i][0] - $lng) * $mPerDegLng;
                $ay = ($line[$i][1] - $lat) * $mPerDegLat;
                $bx = ($line[$i + 1][0] - $lng) * $mPerDegLng;
                $by = ($line[$i + 1][1] - $lat) * $mPerDegLat;

                $dx = $bx - $ax;
                $dy = $by - $ay;
                $lenSq = $dx * $dx + $dy * $dy;
                $t = $lenSq > 0 ? max(0, min(1, -($ax * $dx + $ay * $dy) / $lenSq)) : 0;

                $px = $ax + $t * $dx;
                $py = $ay + $t * $dy;
                $distance = sqrt($px * $px + $py * $py);

                if ($distance < $best['distance']) {
                    $best = [
                        'lat' => $lat + $py / $mPerDegLat,
                        'lng' => $lng + $px / $mPerDegLng,
                        'distance' => $distance,
                    ];
                }
            }
        }

        return $best;
    }

    /**
     * The on-road position for a point, or null when it is too far from the corridor.
     *
     * @return array{lat: float, lng: float, distance: float}|null
     */
    public static function snap(float $lat, float $lng): ?array
    {
        $nearest = self::nearest($lat, $lng);

        return $nearest['distance'] <= self::TOLERANCE_METERS ? $nearest : null;
    }
}
