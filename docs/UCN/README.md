# Use Case Narratives (UCN) — Web Commute

Documentation of the implemented behaviour of the three core guest/commuter journeys.

| Use Case ID | Name | Actor | Document |
|---|---|---|---|
| UCN_SC_E001 | Login | Guest | [UCN_SC_E001_Login.md](UCN_SC_E001_Login.md) |
| UCN_SC_E002 | Create Account | Guest | [UCN_SC_E002_Create_Account.md](UCN_SC_E002_Create_Account.md) |
| UCN_SC_E003 | Track PUJ | Guest | [UCN_SC_E003_Track_PUJ.md](UCN_SC_E003_Track_PUJ.md) |
| UCN_SC_E004 | Plan Trip | Guest / Commuter | [UCN_SC_E004_Plan_Trip.md](UCN_SC_E004_Plan_Trip.md) |
| UCN_SC_E005 | Topup Balance | Commuter | [UCN_SC_E005_Topup_Balance.md](UCN_SC_E005_Topup_Balance.md) |

## Terminology

| Term | Meaning |
|---|---|
| **Guest** | An *unauthenticated* visitor browsing the public map (`/map/guest`). |
| **Commuter** | An authenticated user holding the `commuter` role. A commuter starts as a guest and becomes a commuter by registering (UCN_SC_E002) and verifying their email. |
| **PUJ** | Public Utility Vehicle. On the map, a PUJ marker is a vehicle with a location reported in the last 5 minutes. |
| **Privacy radius** | 200 m. Guest/commuter coordinates are randomly obfuscated within this radius. |

## Document conventions

- Each document follows the classic UCN layout: header, preconditions, **Main Flow**, **Alternate Flow**, **Postconditions**, **Exceptions**, then implementation references.
- Every behaviour described is traceable to the code path listed in the *Implementation References* table.
- Items marked **Known Gaps / Notes** describe deliberate limitations or behaviours that do **not** yet match the original narrative — they are candidates for the next iteration, not bugs.
- Error/warning UI uses a single status banner (`#map-alert`) with the priority stack:
  `offline` → `map-service` → `routing-service` → `location-denied` → `no-puj`.