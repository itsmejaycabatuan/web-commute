# UCN_SC_E020 — View System Reports

| Field                 | Value                                                                                                 |
| --------------------- | ------------------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E020                                                                                           |
| **Use Case Name**     | View System Reports                                                                                    |
| **Primary Actor**     | Admin (`role:admin`)                                                                                  |
| **Secondary Actor**   | System (aggregation engine, CSV writer)                                                               |
| **Goal**               | Allow the Admin to review the system reports for a selected report type and date range, and to export the result. |
| **Trigger**           | Admin clicks the **"Reports & Analytics"** item on the sidebar (`GET /reports`).                        |
| **Preconditions**     | 1. Admin is authenticated and registered to the system.<br>2. Enough data has been generated throughout the system for the selected report type. |
| **Supporting Actors** | Payments, top-ups, drivers + timekeeping, preventive maintenance records                       |

> **Corrections vs. the original narrative**
> - The sidebar item is **"Reports & Analytics"**, not *"Reports & Analytics button"* phrased as *"the 'Reports & Analytics' button"* — the wording matched, but the item is only present on the Admin menu.
> - **The export produces a CSV file, not an Excel file.** The original narrative described an *"Export to excel"* button that "turns the data into an excel file"; the implemented endpoint streams `text/csv` with a `.csv` filename and the button reads **"Export CSV"**. The narrative is corrected here rather than the code.
> - The report type selector offers **three** report types — *Financial Overview*, *Driver Performance*, *Maintenance & Fleet* — which the original did not enumerate.
> - **E2 (aggregation timeout), E3 (no data found) and E4 (data sync delay)** were not implemented in the original narrative; all three now surface as notices on the report page.

---

## MAIN FLOW

| #   | Actor's Action                                                                | System Response                                                                                                        |
| --- | ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------ |
| 1   | Admin opens up the dashboard on the map.                                     | System displays the dashboard with the admin analytics.                                                                   |
| 2   | Admin clicks the **"Reports & Analytics"** item on the sidebar.               | System redirects to the System Reports page (`reports.generate`), displaying the list of available reports, the report-type selector, the *from* / *to* date inputs, the **"Generate Report"** button and the **"Export CSV"** button. |
| 3   | Admin selects a report type & date range and clicks **"Generate Report"**.   | System aggregates the data for the selected type and range and renders the report: **Financial Overview** (revenue, top-ups, transaction count, daily revenue chart), **Driver Performance** (total/active drivers, top 5 performers by hours, status distribution) or **Maintenance & Fleet** (pending/overdue/completed counts, upcoming schedule table; *completed* counts the preventive-maintenance records saved in the selected range). Top-up totals count **completed** top-ups only. |

---

## Alternate Flow

**A1 – Switch to a Different Report Type or Date Range**
1. On the page, the Admin selects a different report type and/or date range and clicks **"Generate Report"**.
2. System re-runs the aggregation for the new selection and re-renders the report; the **"Export CSV"** button stays disabled until the new report has been generated.

**A2 – Export the Report**
1. After generating a report, the Admin clicks **"Export CSV"**.
2. System streams the report data for the current type and range as a **CSV file** (`Content-Type: text/csv`) named `smartcommute_<type>_report_<YYYY-MM-DD>.csv`.
3. The Admin's browser downloads the file. The CSV columns differ per type — e.g. financial: *Date, Type, Reference ID, Amount, User* (fares and wallet top-ups); driver: *Driver Name, Status, Total Hours Worked*; maintenance: *Vehicle Plate, Task, Due Date, Status*.

**A3 – Receive the weekly and monthly report by email (automatic)**
1. Every Monday at 06:00 `php artisan reports:send weekly` and on the 1st at 06:30 `reports:send monthly` build the report for the **last full week (Mon–Sun)** or the **last full calendar month**.
2. Every active Admin is emailed a summary: fare revenue (by method), wallet top-ups, failed payments, violations logged / issued / collected / outstanding, hours worked, overtime and auto-closed shifts, distance driven, maintenance spend, overdue and due-soon services now, repeat violators and commuters with unpaid scans.
3. The figures come as an attached CSV (formula-safe, UTF-8) and, with `REPORT_ATTACH_ACTIVITY_LOG` on (default), the period's audit trail as a second CSV (at most `REPORT_ACTIVITY_LOG_LIMIT` rows). Each send is written to the audit trail (*Scheduled report emailed*).
4. `--to=` sends to one address to try it out; `--dry-run` prints the figures without emailing.

**A4 – Open Fleet Analytics**
1. The Admin clicks **Fleet Analytics** on the sidebar (`GET /analytics`, Admin only) and picks the last **7, 30 or 90 days**.
2. System computes, from time-keeping, the GPS trail, payments, violations and maintenance logs: headline totals (hours, overtime, distance, fare revenue, violations, maintenance cost); an 8-week hours-and-overtime chart; the repeat violators and commuters with unpaid scans; a **driver** table (shifts, hours, overtime, auto-closed shifts, fares and revenue attributed by UCN_SC_E006 A6, violations, fines) and a **vehicle** table (kilometres, hours in service, fares, revenue, share of scans that ended in a fare, maintenance cost).
3. The page is read-only and computed on each visit; kilometres are recorded from the broadcast trail from now on (UCN_SC_E021 A5), so earlier history shows 0 km.

---

## Postconditions

1. The Admin has successfully reviewed the system analytics.
2. The system has successfully generated the reports based on the Admin's preference.
3. The system notifies the Admin with a success message.
4. An export downloads a CSV file that mirrors exactly what was rendered for the current type and range.

5. The Admin receives the previous week's and month's report by email, and can open the same figures per driver and vehicle on Fleet Analytics (A3, A4).
---

## Exceptions

**E1 – Connection Error**
The page or the aggregation fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Data Aggregation Timeout**
The aggregation cannot complete (the database takes too long / the query fails). System cancels the run and shows *"The database is taking too long to generate this report. Please try a shorter date range."* with no partial report.

**E3 – No Data Found**
The selected report type and date range contain nothing to report. System still renders the page and shows *"No data found for the selected report type and date range."* above zeroed figures.

**E4 – Data Sync Delay**
The newest transaction in the ledger is more than a day old, so the analytics are not fully up to date. System warns *"Some of the data may not be included in the analytics — the latest recorded transaction is N day(s) old."*

**E5 – Invalid Date Range**
The *from* date is later than the *to* date. System rejects the request with *"The start date must be before the end date."* and renders no report.

---

## Known Gaps / Notes

- **The export is CSV only.** There is no `.xlsx` writer; opening the file in Excel works but loses column formatting, and a multi-sheet workbook is not produced.
- **Only the financial report is date-filtered end to end** — the driver report uses all-time totals for the driver cards and the maintenance report ignores the range for the pending/overdue buckets (they are computed against *now*, not against *end date*).
- **E2 is a catch-all**: any exception during aggregation (including a genuine bug) is reported to the log and shown to the Admin as an aggregation timeout, so a real error can be mistaken for slowness.
- **E4 is derived from the payments ledger only**; a system with no payments never raises the warning even if other data is stale.
- On-screen reports are generated on every page load from live queries — there is no caching or snapshotting; the weekly and monthly emails (A3) and Fleet Analytics (A4) are computed the same way, at the moment they run or are opened.
- **Exports are audited**: each CSV export writes a `report_exported` entry (who, which report, the date range) that the Admin can find on the Activity Log page (UCN_SC_E028). Generating a report on screen is a read and writes nothing.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `reports.generate` (`GET /reports`), `reports.export` (`GET /reports/export`), both inside `role:admin` |
| Controller | `app/Http/Controllers/ReportController.php::generate()`, `buildReport()`, `assertValidRange()`, `reportIsEmpty()`, `syncLagMinutes()`, `export()` |
| Models | `app/Models/Payment.php`, `app/Models/TopupHistory.php`, `app/Models/Driver.php`, `app/Models/PreventiveMaintenance.php` |
| View | `resources/views/admin/reports.blade.php` (E2 / E3 / E4 notice banners) |
| Menu | `config/menu.php` — *Reports & Analytics* (admin) |
| Middleware | `auth`, `verified`, `role:admin` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_a_report_over_an_empty_window_reports_no_data`, `test_an_inverted_date_range_is_rejected`) |
| Scheduled reports | `app/Services/PeriodReportService.php`, `app/Console/Commands/SendScheduledReport.php` (`reports:send`, Mondays 06:00 and the 1st 06:30), `app/Notifications/ScheduledReport.php`, `config/reports.php` |
| Fleet Analytics | `app/Http/Controllers/Admin/AnalyticsController.php`, `app/Services/FleetAnalyticsService.php`, `resources/views/admin/analytics.blade.php`, route `analytics.index` (inside `role:admin`), `config/menu.php` — *Fleet Analytics* (admin) |
| Tests (automation) | `tests/Feature/AutomationPhase2Test.php` — `test_period_report_totals`, `test_weekly_report_is_emailed_to_the_admins_with_attachments_and_audited`, `test_driver_and_vehicle_stats_add_up`, `test_analytics_page_is_for_the_admin_only` |
| Related | UCN_SC_E019 (Manage Fare Rates — financial figures), UCN_SC_E016 (Maintenance Schedule — maintenance figures), UCN_SC_E013 (Timekeeping — driver hours) |
