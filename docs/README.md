# SmartCommute — documentation

| Document | What it is | Audience |
|---|---|---|
| [UCN/](UCN/README.md) | **Use case narratives** `UCN_SC_E001` – `E029` (E025 unassigned): the implemented behaviour of every feature, with flows, exceptions, known gaps and code references. `UCN_Combined.md` is the same set in one file. | Everyone |
| [Use_Case_Diagram.md](Use_Case_Diagram.md) | UML use-case diagram of the whole system, an actor / use-case catalogue, and the **split diagrams** (PNG) for pasting into Google Docs or Word. Sources and generators in [`diagrams/`](diagrams/). | Analysts, report writers |
| [Functional_Requirements.md](Functional_Requirements.md) | What the system shall do (`FR-…`), traced to the UCNs, plus the documented gaps as candidate requirements (`FR-GAP-…`). | Analysts, testers |
| [Non_Functional_Requirements.md](Non_Functional_Requirements.md) | Performance, security, privacy, reliability, usability, auditability… (`NFR-…`), each marked **Met / Partial / Gap** against the code today, plus what nobody has specified yet. | Analysts, reviewers |
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
- Automated suites: `/opt/lampp/bin/php vendor/bin/phpunit` (they run against the dev database inside transactions, so run `php artisan migrate` first).
