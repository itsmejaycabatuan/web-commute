# UCN_SC_E004 — Plan Trip

| Field                 | Value                                                                                                                                                                             |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E004                                                                                                                                                                       |
| **Use Case Name**     | Plan Trip                                                                                                                                                                         |
| **Primary Actor**     | Guest / Commuter                                                                                                                                                                  |
| **Secondary Actor**   | System                                                                                                                                                                            |
| **Goal**              | Allow Guest to see the distance and fare prices for their commute.                                                                                                                |
| **Trigger**           | Guest opens the sidebar on the map.                                                                                                                                               |
| **Preconditions**     | 1. System routing engine is operational.<br>2. User has an active internet connection.<br>3. Both a start point and a destination have been selected (by map click or by search). |
| **Supporting Actors** | External Routing Service (OSRM), the browser's geolocation, Fare rate table (`FareRate`)                                                                                |

---

## MAIN FLOW

| #   | Actor's Action                                         | System Response                                                                                                                                                                                                     |
| --- | ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User opens the sidebar on the map page.                | System displays a fare calculator with a starting point (the user's own location), a destination button, and distance and fare output fields. The road the service covers (Minglanilla to Cebu IT Park) is drawn as a fixed green line.                                                                                               |
| 2   | User presses **Set destination** and picks a stop from the list (or taps the highlighted road). | System uses the user's GPS position as **Point A**, snapped onto the road, and only lets a destination be set when that position is on or within 150 m of the road (a side road counts). The destination is a fixed stop or a tapped point snapped to the road (within 60 m); it is **Point B**. |
| 3   |                                                        | System requests a driving route from the routing service (OSRM) and draws the recommended route path on the map, then fits the map bounds to it.                                                                    |
| 4   |                                                        | System displays the total **distance (km)** and the **fare cost**, looked up from the active fare-rate table by distance tier.                                                            |

**Fare calculation detail**
- Fare is looked up by finding the **highest distance tier ≤ the calculated distance** (`getFareFromDB()`); values are rounded up to whole pesos.
- The fare table is passed from the server on page render (`FareRate` for the latest `Fare`), so no fare data is fetched from the client.

---

## Alternate Flow

**A1 – Guest taps the road for the destination**
1. Guest presses the destination pin button, then taps a point on the highlighted road.
2. System snaps the point onto the road, labels it *"Near <stop>"*, places the marker and calculates the trip.

**A2 – Guest refreshes the starting point**
1. Guest presses the start-point button; System re-reads the GPS position and sets it as the starting point again (subject to the 150 m route check).

**A3 – Guest changes their mind**
1. Guest presses **"Reset Route"**.
2. System clears both points, the drawn route, and the distance/fare fields.

**A4 – Guest wants to book the trip**
1. Guest presses **"Buy a Ride"** with both points selected.
2. **Guest (unauthenticated):** System stores the chosen points and redirects to the registration page (see UCN_SC_E002 A1).
3. **Commuter:** System redirects to the payment page with the distance and fare (see E005 / payment flow).

**A5 – Guest exceeds the daily limit**
1. Guest is limited to **3 fare calculations per day** (tracked locally per device).
2. On the 4th attempt the System blocks the calculation and shows the daily-limit modal.

---

## Postconditions

1. Users now have the distance and fare price information for their trip.
2. The selected origin, destination and drawn route are visible on the map.

---

## Exceptions

**E1 – Fare Prices Unavailable**
No fare rates exist for the current fare period. The System still displays the calculated distance, but the fare price is shown as **0**.

**E2 – Location Unavailable or Off the Route**
- *No GPS fix:* the System shows the banner *"Waiting for your location — Allow location access so your position can be used as the starting point, then try again."* and the destination cannot be set.
- *Away from the route:* the user's position is more than 150 m from the Minglanilla to IT Park road. The System shows *"You're away from the route — Move onto or near the Minglanilla to IT Park road to set a destination."* and the destination cannot be set. The banners clear automatically.
- *Tap off the road:* a destination tap more than 60 m from the road is rejected with *"Pick a point on the road"*; the System stays in selection mode.

**E3 – Routing API Timeout or Failure**
The external routing service fails to respond, returns a non-`Ok` code, or returns a non-2xx status. The System clears the distance/fare fields and displays a banner: *"Route unavailable — The routing service failed to respond, so the distance and fare could not be calculated. Please try again."* The banner clears automatically on the next successful calculation.

**E4 – No Internet Connection**
Device is offline. The System shows the offline banner (see UCN_SC_E003 E4) and does not attempt the routing call.

**E5 – No Points Selected**
The System cannot calculate a trip until both an origin and a destination are set; the "Buy a Ride" step fails validation with *"Pick-up point and destination is required."*

**E6 – Daily Limit Reached (Guests only)**
Guest has used all 3 daily fare calculations. The System displays the limit modal and cancels the calculation.

---

## Known Gaps / Notes

- The route drawn is **origin → destination** only; it is *not* a route to the nearest PUJ.
- The daily guest limit is stored in `localStorage` and can be bypassed by clearing browser storage.

---

## Implementation References

| Element | Location |
|---|---|
| Fare calculator UI | `resources/views/map.blade.php` (left sidebar, `#distance`, `#price-regular`) |
| Route calculation | `resources/views/map.blade.php::calculateRoute()` → OSRM `router.project-osrm.org` |
| Fare lookup | `resources/views/map.blade.php::getFareFromDB()` + `@json($rates)` |
| Destination stops + start-point check | `resources/views/map.blade.php` (stop picker, `onUserLocationFix`), `App\Support\ServiceCorridor` |
| Static road | `resources/geo/minglanilla-itpark-corridor.geojson` |
| Route layers | `resources/views/map.blade.php` → `route`, `route-line`, `route-line-glow` |
| Point markers | `resources/views/map.blade.php` → `pickup-point`, `destination-point` sources |
| Guest daily limit | `resources/views/map.blade.php` → `DAILY_LIMIT`, `toggleLimitModal()` |
| Status banner | `resources/views/map.blade.php` → `#map-alert`, `showMapAlert()` |
| Payment handoff | `app/Http/Controllers/PaymentController.php::index()` |