<?php

namespace App\Services;

use App\Models\Fare;
use App\Models\FareRate;
use RuntimeException;

/**
 * Server-side fare pricing.
 *
 * The fare calculator on the map computes the fare in JavaScript and posts the
 * result back as a hidden field. That value is client-controlled, so it is
 * never trusted: this service re-prices the trip from the trip distance using
 * the published rate table, mirroring the JS `getFareFromDB()` implementation.
 *
 * NOTE: the *distance* is still supplied by the client (it comes from the
 * browser's OSRM call). Closing that last gap means routing the trip
 * server-side — see UCN_SC_E006 Known Gaps.
 */
class FareCalculator
{
    /**
     * Rates for the most recently uploaded fare table, ordered by tier.
     */
    public function rates(): array
    {
        $latestFare = Fare::orderBy('id', 'desc')->first();

        if (! $latestFare) {
            return [];
        }

        return FareRate::where('fare_id', $latestFare->id)
            ->orderBy('km')
            ->get()
            ->all();
    }

    /**
     * The regular (non-discounted) fare for a trip of $distanceKm kilometres.
     *
     * Tiering mirrors the client: use the highest tier whose `km` is <= the trip
     * distance; if the trip is shorter than the first tier, use the first tier.
     */
    public function regularFare(float $distanceKm): float
    {
        $rates = $this->rates();

        if (empty($rates)) {
            throw new RuntimeException('No fare rates are published for the current fare table.');
        }

        $tier = null;
        foreach ($rates as $rate) {
            if ((float) $rate->km <= $distanceKm) {
                $tier = $rate;
            }
        }

        $tier = $tier ?? $rates[0];

        return (float) ceil((float) $tier->regular);
    }
}