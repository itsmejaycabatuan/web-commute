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
| **Supporting Actors** | Payments, top-ups, drivers + timekeeping, preventive maintenance and service logs                       |

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
| 3   | Admin selects a report type & date range and clicks **"Generate Report"**.   | System aggregates the data for the selected type and range and renders the report: **Financial Overview** (revenue, top-ups, transaction count, daily revenue chart), **Driver Performance** (total/active drivers, top 5 performers by hours, status distribution) or **Maintenance & Fleet** (pending/overdue/completed counts, upcoming schedule table). |

---

## Alternate Flow

**A1 – Switch to a Different Report Type or Date Range**
1. On the page, the Admin selects a different report type and/or date range and clicks **"Generate Report"**.
2. System re-runs the aggregation for the new selection and re-renders the report; the **"Export CSV"** button stays disabled until the new report has been generated.

**A2 – Export the Report**
1. After generating a report, the Admin clicks **"Export CSV"**.
2. System streams the report data for the current type and range as a **CSV file** (`Content-Type: text/csv`) named `smartcommute_<type>_report_<YYYY-MM-DD>.csv`.
3. The Admin's browser downloads the file. The CSV columns differ per type — e.g. financial: *Date, Type, Reference ID, Amount, User* (fares and wallet top-ups); driver: *Driver Name, Status, Total Hours Worked*; maintenance: *Vehicle Plate, Task, Due Date, Status*.

---

## Postconditions

1. The Admin has successfully reviewed the system analytics.
2. The system has successfully generated the reports based on the Admin's preference.
3. The system notifies the Admin with a success message.
4. An export downloads a CSV file that mirrors exactly what was rendered for the current type and range.

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
- Reports are generated on every page load from live queries — there is no caching, snapshotting or scheduled export.
- No audit trail: reports are not recorded, and an Admin cannot see who exported what.

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `reports.generate` (`GET /reports`), `reports.export` (`GET /reports/export`), both inside `role:admin` |
| Controller | `app/Http/Controllers/ReportController.php::generate()`, `buildReport()`, `assertValidRange()`, `reportIsEmpty()`, `syncLagMinutes()`, `export()` |
| Models | `app/Models/Payment.php`, `app/Models/TopupHistory.php`, `app/Models/Driver.php`, `app/Models/PreventiveMaintenance.php`, `app/Models/VehicleMaintenanceLog.php` |
| View | `resources/views/admin/reports.blade.php` (E2 / E3 / E4 notice banners) |
| Menu | `config/menu.php` — *Reports & Analytics* (admin) |
| Middleware | `auth`, `verified`, `role:admin` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_a_report_over_an_empty_window_reports_no_data`, `test_an_inverted_date_range_is_rejected`) |
| Related | UCN_SC_E019 (Manage Fare Rates — financial figures), UCN_SC_E016 (Maintenance Schedule — maintenance figures), UCN_SC_E013 (Timekeeping — driver hours) |