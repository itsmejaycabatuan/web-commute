# SmartCommute — documentation

| Document | What it is | Audience |
|---|---|---|
| [UCN/](UCN/README.md) | **Use case narratives** `UCN_SC_E001` – `E028`: the implemented behaviour of every feature, with flows, exceptions, known gaps and code references. `UCN_Combined.md` is the same set in one file. | Everyone |
| [Use_Case_Diagram.md](Use_Case_Diagram.md) | UML use-case diagram of the whole system, an actor / use-case catalogue, and the **split diagrams** (PNG) for pasting into Google Docs or Word. Sources and generators in [`diagrams/`](diagrams/). | Analysts, report writers |
| [Functional_Requirements.md](Functional_Requirements.md) | What the system shall do: **one requirement per use case** (`FR-E001` = `UCN_SC_E001`, 28 in all), each with its acceptance criteria, plus the known gaps (`GAP-…`, not requirements). | Analysts, testers |
| [Non_Functional_Requirements.md](Non_Functional_Requirements.md) | Security, privacy, reliability, performance, usability… (`NFR-…`, 77 in all), **each tied to evidence**: the tests that verify it and their result (✅ Pass / ❌ Fail / ⚪ Not tested). Known gaps are executable tests that fail on purpose (`--group nfr-gap`). Also lists the defects the tests found. | Analysts, reviewers, testers |
| [Entity_Relationship_Diagram.md](Entity_Relationship_Diagram.md) | Summary and full ERD (27 tables), table groups, and schema findings the UCNs do not state. | Developers, report writers |
| [AUDIT_TRAIL.md](AUDIT_TRAIL.md) | How the audit trail (`activity_log`) works: what is recorded, the event catalogue, how to add to it, the admin Activity Log page, and how to roll it out to another environment. | Developers |
| [MANUAL_TEST_CHECKLIST.md](MANUAL_TEST_CHECKLIST.md) | Click-through checks by role, with seeded test accounts, and what each test round found. | Testers |
| [PAYMONGO_SETUP.md](PAYMONGO_SETUP.md) | Enabling GCash / Maya checkout with PayMongo sandbox keys. | Developers |

## Regenerating the diagrams

| Diagram | Command | Needs |
|---|---|---|
| Full use-case diagram | `python3 docs/diagrams/build_use_case_diagram.py` | Python 3 |
| Split use-case diagrams (SVG + PNG) | `python3 docs/diagrams/build_use_case_parts.py` | Python 3, Chrome (for the PNGs) |
| Summary + full ERD (SVG + PNG) | `python3 docs/diagrams/build_erd.py` | Python 3, Graphviz |

Edit the data block at the top of each script (use cases, groups, schema), then re-run it. After a migration, update `SCHEMA` / `REL` in `build_erd.py`.

## Keeping them honest

- Behaviour change → update its UCN (and `UCN_Combined.md`), then the matching `FR-` / `NFR-` rows.
- New migration → update the ERD data and `Entity_Relationship_Diagram.md`.
- New use case → add a UCN sheet, a row in `UCN/README.md` and `UCN_Combined.md`, then the use-case diagram data and `Use_Case_Diagram.md`.
- Automated suites: `/opt/lampp/bin/php vendor/bin/phpunit` (they run against the dev database inside transactions, so run `php artisan migrate` first). `tests/Feature/Nfr/` holds the tests behind each non-functional requirement; `NfrTraceabilityTest` fails if that document cites a test that does not exist or its counts drift, and if the functional requirements stop being one per use case.
