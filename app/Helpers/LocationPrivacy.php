<?php

namespace App\Helpers;

class LocationPrivacy
{
    private static int $radiusMeters = 200;

    public static function obfuscate(float $lat, float $lng): array
    {
        $latDegPerM = 1 / 111320;
        $lngDegPerM = 1 / (111320 * cos(deg2rad($lat)));

        $angle = mt_rand(0, 3600000) / 1000000 * M_PI;
        $distance = sqrt(mt_rand() / mt_getrandmax());

        return [
            'lat' => round($lat + $distance * self::$radiusMeters * $latDegPerM * cos($angle), 6),
            'lng' => round($lng + $distance * self::$radiusMeters * $lngDegPerM * sin($angle), 6),
            'privacy_radius' => self::$radiusMeters,
        ];
    }
}
