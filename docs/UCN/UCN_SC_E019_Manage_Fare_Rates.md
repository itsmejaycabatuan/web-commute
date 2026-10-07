# UCN_SC_E019 — Manage Fare Rates

| Field                 | Value                                                                                          |
| --------------------- | ----------------------------------------------------------------------------------------------- |
| **Use_Case_ID**       | UCN_SC_E019                                                                                    |
| **Use Case Name**     | Manage Fare Rates                                                                              |
| **Primary Actor**     | Admin (`role:admin`)                                                                           |
| **Secondary Actor**   | System (PDF parser, Python runtime)                                                             |
| **Goal**               | Allow the Admin to upload the official fare guide and to edit or upload the fare rates in the system. |
| **Trigger**           | Admin clicks the **"Fare Rates"** item on the sidebar (`GET /fares`).                            |
| **Preconditions**     | 1. Admin is authenticated and registered to the system.<br>2. Fare rate data already exists in the database. |
| **Supporting Actors** | Fare, Fare rate, PDF extraction script                                                         |

> **Corrections vs. the original narrative**
> - The sidebar item is **"Fare Rates"**; the upload control is a **"Select File"** file input next to an **"Upload"** button (a `PUT` multipart form), not a plain "select file button".
> - The edit button is **"Edit Rates"** and the commit button is **"Save Changes"**, both in the fares toolbar; the original called the entry point *"Edit Rates button"* and left the save button unnamed.
> - **E2 (rates empty) and E4 (rate cannot be zero or negative)** were not implemented in the original narrative and are now validated before any write.
> - The uploaded document must be a **PDF** (`file, mimes:pdf`); the parser (`extractPdf.py`) is run through the project virtualenv and its JSON output is what populates the rate tiers.

---

## MAIN FLOW

| #   | Actor's Action                                                    | System Response                                                                                     |
| --- | ------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| 1   | Admin opens up the dashboard on the map.                           | System displays the dashboard with the admin analytics.                                                |
| 2   | Admin clicks the **"Fare Rates"** item on the sidebar.             | System redirects to the Fare Rates page (`fares.index`), showing the tier count and the min/max fare summary strip. |
| 3   |                                                                            | System displays the rate table with columns **Distance (km)**, **Regular** and **Discount**, the **"Edit Rates"** button and the PDF upload form. |

> Steps 4+ are the alternate flows (A1–A2); the main flow is "open the table and read it".

---

## Alternate Flow

**A1 – Upload Fare Rates to the System**
1. On the page, the Admin selects a file with **"Select File"** — an official **LFTFRB fare guide (PDF)** — then presses **"Upload"**.
2. System validates the upload: `fare` **required, file, mimes:pdf**.
3. System stores the file and runs the extraction script (`resources/scripts/extractPdf.py`) inside the project virtualenv, then parses the returned JSON.
4. Inside a transaction, System creates the **Fare** record and inserts the parsed tiers (`fare_rates`: km, regular, discount) for the 1–25 km and 27–51 km bands, then confirms *"File uploaded successfully!"*

**A2 – Edit One or More Fare Rates**
1. On the page, the Admin clicks **"Edit Rates"**.
2. System switches the Regular and Discount cells into editable number inputs and reveals the *"Editing Mode"* save bar.
3. Admin edits one or multiple fare rates and clicks **"Save Changes"**.
4. System validates the whole payload (E2, E4) and, in one transaction, updates each `fare_rates` row, then confirms *"Rates updated successfully"*.

---

## Postconditions

1. The Admin has successfully uploaded or modified the fare rates.
2. The fare rate record in the database has been updated (`fares` and `fare_rates`).
3. The system notifies the Admin with a success message.
4. The current rate set is what fare pricing and the map's rate display read from.

---

## Exceptions

**E1 – Connection Error**
The page or the write fails because the actor is offline or the service is unreachable. The action is cancelled and the actor is asked to check their connection.

**E2 – Rates Empty**
The save was submitted with no rate rows at all (nothing to persist). System rejects the submission with *"There are no rates to save."* and performs no write.

**E3 – Database Error**
The insert/update fails at the database level. System cancels the operation and notifies the Admin.

**E4 – Rate Cannot be Zero or Negative**
A submitted Regular or Discount value is `0` or below. System rejects the submission with *"A fare rate must be greater than zero."* / *"A discount rate must be greater than zero."*, leaving every previous value untouched.

**E5 – Document File Is Required**
No file was selected, or the selected file is not a PDF. System rejects the upload with the corresponding validation message and stores nothing.

---

## Known Gaps / Notes

- **The extracted tiers are hard-coded to two bands** (rows 1–25 and 27–51 of the parser output). A fare guide with a different layout is rejected at parse time rather than supported.
- **Validation is all-or-nothing**: one invalid row blocks the entire save, and the admin must re-enter every value.
- **The extraction script runs synchronously** and shells out to the project virtualenv; a slow or missing interpreter fails the whole upload.
- Rates are decimals without rounding rules, and there is no per-tier effective date — uploading a new guide replaces the active rate set wholesale.
- Uploading does not remove the previous fare set; multiple `fares` rows can coexist and only the latest is used.

---

## Implementation References

| Element | Location |
|---|---|
| Controller | `app/Http/Controllers/FareController.php::index()`, `view()`, `upload()`, `bulkUpdate()` |
| Models | `app/Models/Fare.php`, `app/Models/FareRate.php` |
| Parser | `resources/scripts/extractPdf.py`, run via `venv/bin/python3` |
| View | `resources/views/admin/fares/index.blade.php` (validation banner added for E2/E4) |
| Menu | `config/menu.php` — *Fare Rates* (admin) |
| Middleware | `auth`, `verified`, `role:admin` |
| Tests | `tests/Feature/UcnStaffRegressionTest.php` (`test_a_zero_or_negative_fare_rate_is_rejected`, `test_an_empty_rate_submission_is_rejected`, `test_a_valid_rate_submission_is_saved`) |
| Related | UCN_SC_E006 (Pay Fare — prices a ride from the active rate set), UCN_SC_E020 (System Reports — financial report) |