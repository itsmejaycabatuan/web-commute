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
| **Supporting Actors** | External Geocoding Service (Photon), External Routing Service (OSRM), Fare rate table (`FareRate`)                                                                                |

---

## MAIN FLOW

| #   | Actor's Action                                         | System Response                                                                                                                                                                                                     |
| --- | ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | User opens the sidebar on the map page.                | System displays a fare calculator with input fields for origin and destination, plus distance and fare output fields.                                                                                               |
| 2   | User selects Origin and destination (via map or text). | System places **Point A** and **Point B** markers on the map. Text entries are resolved by the geocoding service (Photon), restricted to the service-area bounding box; a tap on the map places the point directly. |
| 3   |                                                        | System requests a driving route from the routing service (OSRM) and draws the recommended route path on the map, then fits the map bounds to it.                                                                    |
| 4   |                                                        | System displays the total **distance (km)** and the **fare costs** (regular and discounted), looked up from the active fare-rate table by distance tier.                                                            |

**Fare calculation detail**
- Fare is looked up by finding the **highest distance tier ≤ the calculated distance** (`getFareFromDB()`); values are rounded up to whole pesos.
- The fare table is passed from the server on page render (`FareRate` for the latest `Fare`), so no fare data is fetched from the client.

---

## Alternate Flow

**A1 – Guest selects points by tapping the map**
1. Guest taps **"Set pick-up"** then clicks a point on the map (or vice versa for the destination).
2. System places the marker and reverse-geocodes the point to a readable address.

**A2 – Guest searches for a place by name**
1. Guest types at least 2 characters into the origin or destination field.
2. System queries the geocoding service and lists up to 6 matches inside the service area.
3. Guest selects a result; System places the marker and calculates the trip.

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

**E2 – Location / Place Not Found**
- *No search match:* the System shows **"No results found"** in the dropdown and a banner: *"Location not found — That place could not be found. Try a different name, or tap a point directly on the map."* The banner clears after ~6 seconds.
- *Reverse-geocode failure:* the System falls back to displaying the raw coordinates (`lat, lon`) and **still places the marker**.

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
- Fares are passed to the payment page as client-side values; the fare amount should be re-validated server-side at payment time.
- The daily guest limit is stored in `localStorage` and can be bypassed by clearing browser storage.

---

## Implementation References

| Element | Location |
|---|---|
| Fare calculator UI | `resources/views/map.blade.php` (left sidebar, `#distance`, `#price-regular`, `#price-discount`) |
| Route calculation | `resources/views/map.blade.php::calculateRoute()` → OSRM `router.project-osrm.org` |
| Fare lookup | `resources/views/map.blade.php::getFareFromDB()` + `@json($rates)` |
| Place search | `resources/views/map.blade.php::searchPlaces()` → Photon `photon.komoot.io` |
| Reverse geocoding | `resources/views/map.blade.php` (reverse geocode call) |
| Route layers | `resources/views/map.blade.php` → `route`, `route-line`, `route-line-glow` |
| Point markers | `resources/views/map.blade.php` → `pickup-point`, `destination-point` sources |
| Guest daily limit | `resources/views/map.blade.php` → `DAILY_LIMIT`, `toggleLimitModal()` |
| Status banner | `resources/views/map.blade.php` → `#map-alert`, `showMapAlert()` |
| Payment handoff | `app/Http/Controllers/PaymentController.php::index()` |