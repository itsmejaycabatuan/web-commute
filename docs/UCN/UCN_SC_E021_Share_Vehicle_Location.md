# UCN_SC_E021 — Share Vehicle Location (Driver Broadcast)

| Field                 | Value                                                                                                                                                        |
| --------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E021                                                                                                                                                  |
| **Use Case Name**     | Share Vehicle Location (Driver Broadcast)                                                                                                                   |
| **Primary Actor**     | Driver (signed in, holding an approved `Driver` profile, assigned a vehicle)                                                                                 |
| **Secondary Actor**   | System, Push channel (Pusher/Echo)                                                                                                                          |
| **Goal**               | Publish the driving vehicle's position so guests and commuters can track it on the map (UCN_SC_E003), while keeping the exact coordinates private.          |
| **Trigger**           | The driver's map view receives a browser geolocation fix for their assigned vehicle.                                                                          |
| **Preconditions**     | 1. Driver is signed in with the `driver` role and has a `Driver` profile.<br>2. A vehicle is assigned to that driver (`vehicles.driver_id`).<br>3. The account is not suspended.<br>4. The driver's device has location permission and a network connection. |
| **Supporting Actors** | `vehicle_locations` (current position), `vehicle_location_histories` (breadcrumb), `vehicles.is_active`, Pusher channel `vehicle-locations`                        |

> **Why this document exists.** UCN_SC_E003 (*Track PUJ*) documents the **read** side of live tracking from the guest/commuter's point of view. This document covers the **write** side — the authenticated driver continuously publishing the assigned vehicle's position — which had no narrative of its own. The privacy rules referenced in E003 are implemented here and are summarised in step 8 below.

---

## MAIN FLOW

| #   | Actor's Action                                                          | System Response                                                                                                                                                                                     |
| --- | ------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Driver opens the map view with their vehicle.                            | System renders the map for a driver and exposes their assigned vehicle id; the browser starts watching `geolocation`.                                                                               |
| 2   | The browser obtains a position fix.                                     | *(no system response — the fix is read client-side)*                                                                                                                                               |
| 3   |                                                                          | The browser **throttles to one broadcast every 2 seconds** and POSTs `{ vehicle_id, user_id, latitude, longitude, speed, accuracy, timestamp }` to `/track/vehicle/broadcast` with the CSRF token.     |
| 4   |                                                                          | System validates the payload: `vehicle_id` **required, string**, `user_id` **required, integer**, `latitude` / `longitude` **required, numeric**, `speed` / `accuracy` **nullable, numeric**, `timestamp` **nullable, integer**. |
| 5   |                                                                          | System resolves the actor and refuses the request unless they are signed in (**E2**), hold the `driver` role and have a driver profile (**E2**), are not suspended (**E4**), and the vehicle is assigned to them (**E3**). |
| 6   |                                                                          | System checks whether a row for `(vehicle_id, timestamp)` already exists. If it does, it makes no write and answers `{ success: true, duplicate: true }` (**A1**).              |
| 7   |                                                                          | System upserts the current position into `vehicle_locations` (`user_id`, `latitude`, `longitude`, `speed`, `accuracy`, `last_update = now`) and marks the vehicle `is_active = true`.       |
| 8   |                                                                          | System **fires `LocationUpdated`**, which obfuscates the coordinates to a random point within a **200 m radius** and publishes `{ vehicleId, lat, lng, privacy_radius, speed, accuracy, timestamp, user_id }` on the Pusher channel `vehicle-locations` as `vehicle-location-updated`. **The exact coordinates are never published.** |
| 9   |                                                                          | System appends a breadcrumb row to `vehicle_location_histories` and answers `200 { success: true, duplicate: false, location_id }`.                                                                       |
| 10  |                                                                          | Guests and commuters receive the push event and move that PUJ's marker on their map (UCN_SC_E003 step 3); the "active" freshness window is 5 minutes.                                             |

---

## Alternate Flow

**A1 – Duplicate Broadcast (Retry / Replay)**
1. The browser re-sends a payload whose `timestamp` has already been recorded for that vehicle.
2. System logs the duplicate, performs **no** write and no event, and answers `200 { success: true, duplicate: true, message: "Broadcast already recorded at this timestamp." }` — so a retried request is safe and idempotent.

**A2 – Temporary Failure (Retry Queued)**
1. The database write throws.
2. System logs the failure, dispatches `RetryBroadcastLocation` with a 5-second delay and answers `500 { success: false, retry: true, retry_after: 5, message: "Temporary failure – retrying in 5 seconds." }`.
3. The queued job re-publishes the `LocationUpdated` event **once** and then stops, so the driver's last known position still reaches the map.

**A3 – No Vehicle Assigned**
1. The driver opens the map without an assigned vehicle.
2. The browser never broadcasts (`isBroadcastingDriver` is false), and any manual call is refused (**E3**). The driver simply does not appear on the map.

**A4 – Developer Markers (local environment only)**
1. In the `local` environment only, an admin can add / toggle / remove synthetic map markers from the map view.
2. System stores them as `dev_markers` scoped to that admin and renders them alongside the live PUJs. Outside `local` the feature is compiled out entirely (the marker list is empty), so it cannot leak into production.

---

## Postconditions

1. The vehicle's current position in `vehicle_locations` has been refreshed (`last_update = now`).
2. The vehicle is flagged `is_active`, and the scheduled `vehicle:offline-check` (every minute) flips it back once no broadcast has arrived for 5 minutes.
3. A breadcrumb row has been appended to `vehicle_location_histories`.
4. Guests and commuters see the vehicle move on the map, at an **obfuscated** position (200 m radius) with `privacy_radius` included in the payload.
5. The driver account and the vehicle's trip history are untouched by this flow.

---

## Exceptions

**E1 – Validation Error**
A required field is missing or malformed (e.g. non-numeric latitude). System answers `422` with the field errors and writes nothing.

**E2 – Not Signed In / Not a Driver / No Driver Profile**
The request comes from a guest, a commuter, a manager, or a driver without a `Driver` row. System answers `403` (*"Sign in as a driver to broadcast your vehicle location."* / *"Only drivers can broadcast a vehicle location."*) and writes nothing.

**E3 – Vehicle Not Assigned**
The broadcast vehicle is not assigned to the broadcasting driver. System answers `403` (*"This vehicle is not assigned to you."*) and writes nothing — the endpoint cannot be used to track another driver's vehicle.

**E4 – Suspended Driver**
The driver's account has been suspended (UCN_SC_E012 A5). System answers `403` (*"Your account is suspended."*) and writes nothing, so a suspension takes effect on the map immediately, without waiting for the session to expire.

**E5 – Offline / Network Failure**
The browser cannot reach the endpoint (or the response is dropped). The `fetch` promise rejects, the client logs a console warning, and the position is **not** retried by the browser; the vehicle simply keeps its previous position until the 5-minute freshness window closes.

**E6 – Malformed Payload on Retry**
The queued retry payload is missing a required key. `RetryBroadcastLocation` returns without publishing.

---

## Known Gaps / Notes

- **No server-side rate limiting.** The 2-second throttle is client-side only (`resources/views/map.blade.php`); a modified client could flood the endpoint.
- **The route carries no `auth` middleware** — authorization is performed inside the controller, which is why E2/E3/E4 are explicit checks rather than a middleware result.
- **The breadcrumb always stores `distance_from_last_pos = 0`.** The controller has a `haversineDistance()` helper but never computes the real delta, so historical distance travelled cannot be derived from the table.
- **Without a `timestamp` the upsert key is the vehicle alone**, so a driver whose browser clock never sends one collapses onto a single row; the duplicate guard (A1) also cannot apply.
- **`is_active` is only maintained by the scheduler**, not by the read path: `getActiveVehicles()` decides visibility from `last_update` + vehicle status, so the column can drift if the scheduler has not run.
- **`speed` and `accuracy` are stored but not used** for ETA (E003 computes it from a fixed 20 kph), and the obfuscated radius (200 m) is a constant, not per-user.
- **No history retention policy**: `vehicle_location_histories` grows indefinitely.
- The dev-marker endpoints are `local`-gated in the view but the **routes themselves are not role-gated**; they are simply unused in production.

---

## Implementation References

| Element | Location |
|---|---|
| Route | `routes/web.php` → `POST /track/vehicle/broadcast` (`vehicle.broadcast`), registered outside the `auth` groups |
| Controller | `app/Http/Controllers/VehicleTrackingController.php::broadcastLocation()` (authorization, dedupe, upsert, event, history) |
| Read side | `app/Http/Controllers/VehicleTrackingController.php::getActiveVehicles()` — 5-minute freshness window + vehicle status filter |
| Event | `app/Events/LocationUpdated.php` — channel `vehicle-locations`, event `vehicle-location-updated`, payload built in `broadcastWith()` |
| Retry | `app/Jobs/RetryBroadcastLocation.php` (queued 5 s after a failed write, republishes once) |
| Privacy | `app/Helpers/LocationPrivacy.php::obfuscate()` — random offset within a 200 m radius, applied to both the pushed event and `getActiveVehicles()` |
| Models | `app/Models/VehicleLocation.php`, `app/Models/VehicleLocationHistory.php`, `app/Models/DevMarker.php` |
| Scheduler | `app/Console/Commands/VehicleOfflineCheck.php` — `vehicle:offline-check`, every minute (`app/Console/Kernel.php`) |
| Dev markers | `app/Http/Controllers/DevMarkerController.php`, routes `driver.dev.*` |
| Client | `resources/views/map.blade.php` — `broadcastDriverLocation()`, 2-second throttle, broadcast only when the actor is a driver with an assigned vehicle |
| Tests | `tests/Feature/VehicleTrackingControllerTest.php`, `tests/Feature/UcnSuspensionAndMaintenanceLogTest.php::test_a_suspended_driver_cannot_broadcast_a_location` |
| Related | UCN_SC_E003 (Track PUJ — the read side), UCN_SC_E009/E010 (clock in / out), UCN_SC_E012 (Manage Drivers — suspension), UCN_SC_E015 (Manage Vehicles — status drives map visibility) |