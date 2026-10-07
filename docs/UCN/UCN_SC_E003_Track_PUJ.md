# UCN_SC_E003 — Track PUJ

| Field                 | Value                                                                                                                                                                                                                                   |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN SC E003                                                                                                                                                                                                                             |
| **Use Case Name**     | Track PUJ                                                                                                                                                                                                                               |
| **Primary Actor**     | Guest (unauthenticated visitor)                                                                                                                                                                                                         |
| **Secondary Actor**   | System                                                                                                                                                                                                                                  |
| **Goal**              | Allow Guest to view PUJ locations on the map.                                                                                                                                                                                           |
| **Trigger**           | Guest redirects to the map.                                                                                                                                                                                                             |
| **Preconditions**     | 1. The mapping/tile service is reachable.<br>2. The Guest's device has a network connection.<br>3. *(Optional)* Guest GPS is enabled and location permission is granted — if not granted the System runs in **degraded mode** (see E2). |
| **Supporting Actors** | System, External Mapping Service (MapLibre GL + OpenFreeMap tiles), Push channel (Pusher/Echo)                                                                                                                                          |

---

## MAIN FLOW

| #   | Actor's Action                                 | System Response                                                                                                                                                          |
| --- | ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 1   | Guest opens the map.                           | System requests device location (when permission is granted) and displays the interactive map with navigation, pitch and locate (📍) controls.                           |
| 2   |                                                | System centres the map on the Guest's current location (zoom 15) as soon as the first position fix arrives. Until then the map opens at the default service-area centre. |
| 3   |                                                | System fetches real-time PUJ data (initial fetch + websocket push + polling fallback) and plots the active PUJ markers on the map.                                       |
| 4   | Guest clicks a specific PUJ marker on the map. | System displays an info popup for the PUJ.                                                                                                                               |
| 5   |                                                | **Info popup displays:** PUJ plate/route, approximate location (privacy-obfuscated, ~200 m radius), vehicle status, and ETA to the Guest's location.                     |

**Marker / ETA detail**
- Active PUJs are those with a location reported within the last **5 minutes**.
- Coordinates are randomly obfuscated within a **200 m radius** before being sent to guests/commuters (`App\Helpers\LocationPrivacy`).
- ETA is an estimate: straight-line (Haversine) distance minus half the privacy radius, at an average speed of **20 kph**, plus a ±privacy buffer — displayed as a range or "~N min".
- ETA badges appear on markers within **5 km**; the floating "Nearest PUJ" indicator within **10 km**.

---

## Alternate Flow

**A1 – Guest wants to check the nearest PUJ in their area**
1. Once the Guest's location is known, the System automatically selects the PUJ nearest to the Guest and shows a floating **"Nearest PUJ"** indicator at the bottom of the map (ETA + distance).
2. Guest taps the indicator.
3. System flies the map to that PUJ and automatically opens its info popup.

**A2 – Guest wants to centre the map manually**
1. Guest taps the 📍 locate control.
2. System centres the map and starts tracking position updates to refresh ETA badges and popups.

---

## Postconditions

1. Guests are able to see the nearest PUJ in their area (floating indicator, ≤ 10 km, requires location permission).
2. Guests now have information about the PUJs in their area (markers, ETAs, popup details).

---

## Exceptions

**E1 – No PUJ Markers**
There are no active PUJs (no driver location reported in the last 5 minutes), so there are no markers on the map. System displays a status banner: **"No PUJs available — No PUJ drivers are clocked in right now, so there are no markers on the map."** The banner clears automatically as soon as a PUJ appears.

**E2 – Location Permission Denied / Unavailable**
Guest refuses or cannot give location access. System cannot centre the map or calculate ETA. System displays a status banner: **"Location access needed — Enable location access to see the map centred on you and to get PUJ ETAs."** The map, markers and popups remain usable; only centring and ETA are degraded. The banner clears automatically once a position fix is received.

**E3 – External Map Service Down**
The mapping API fails to load or repeated tile/style errors occur. System displays a status banner: **"Map service unavailable — The mapping service failed to load. Please refresh the page or try again later."** The banner clears automatically when the style loads successfully.

**E4 – No Internet Connection**
Guest's device is offline. System cannot fetch the map or PUJ coordinates. System displays a status banner: **"No internet connection — You are offline. The map and PUJ locations cannot be refreshed until the connection returns."** Marker and vehicle polling are suspended; they resume automatically when the connection returns.

- `routing-service` added to banner priority stack.
**Status banner priority** (only the highest-priority banner is shown):
`offline` → `map-service` → `routing-service` → `location-denied` → `no-puj`

---

## Known Gaps / Notes

- `/api/markers` (developer/mock markers) returns an empty array outside the `local` environment; in production the map relies solely on live driver broadcasts from `/track/vehicles/active`.
- "Active PUJ" is determined by **location freshness (5 minutes)**, not by the driver's clock-in (timekeeping) status. Align either the wording of E1 or the backend rule.
- A1 is a **passive, automatic** indicator limited to 10 km; there is no explicit "nearest PUJ" button.
- Guest guests are limited to **3 map interactions per day** (fare/route lookups) tracked in `localStorage`.

---

## Implementation References

| Element | Location |
|---|---|
| Guest map route | `routes/web.php` → `GET /map/guest` (`guest` middleware) |
| Authenticated map route | `routes/web.php` → `GET /map` (`auth`, `verified`) |
| Map view (all map logic) | `resources/views/map.blade.php` |
| Vehicle tracking helper | `resources/js/map-tracker.js` |
| Marker data API | `routes/api.php` → `GET /api/markers` |
| Live vehicle data API | `routes/web.php` → `GET /track/vehicles/active` |
| Location broadcast | `routes/web.php` → `POST /track/vehicle/broadcast` — the **write** side of live tracking is documented separately in UCN_SC_E021 |
| Live channel | Pusher/Echo channel `vehicle-locations`, event `.vehicle-location-updated` |
| Coordinate obfuscation | `app/Helpers/LocationPrivacy.php` |
| Active-vehicle rule | `app/Http/Controllers/VehicleTrackingController.php::getActiveVehicles()` |