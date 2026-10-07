# UCN_SC_E022 — Driver Registration

| Field                 | Value                                                                                                                                            |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Use_Case_ID**       | UCN SC E022                                                                                                                                      |
| **Use Case Name**     | Driver Registration                                                                                                                              |
| **Primary Actor**     | Guest (unauthenticated visitor)                                                                                                                  |
| **Secondary Actor**   | System                                                                                                                                           |
| **Goal**              | Allow a guest to register as a driver, submit required details and license documentation, and await admin approval before being able to sign in. |
| **Trigger**           | Guest clicks the "Register as Driver" link on the landing/registration page.                                                                     |
| **Preconditions**     | 1. Guest does not currently hold an active, authenticated session in the system.<br>2. The email address is not yet used by an existing account. |
| **Supporting Actors** | System (database, file storage), Mail service (verification link)                                                                                |

---

## MAIN FLOW

| #   | Actor's Action                                                                    | System Response                                                                                                                                                                                           |
| --- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Guest clicks the "Register as Driver" link.                                       | System displays the driver registration form (name, email, contact information, password, confirm password, Terms & Conditions checkbox, driver license image upload).                                   |
| 2   | Guest enters their full name, email address, contact information, and creates a password. | System masks both password inputs (eye toggle available for each).                                                                                                                                        |
| 3   | Guest uploads a clear image of their driver's license (JPG, JPEG, PNG, max 2 MB).  | System validates the file type and size client‑side; the image is stored temporarily for upload.                                                                                                        |
| 4   | Guest checks the "I agree to Terms & Conditions" box and clicks "Submit Registration". | System validates all inputs **server-side** (`required`, `email`, `unique`, `string`, `max:255` for name/contact, `min:8` for password, `same` for password confirmation, `accepted` for terms, `image|mimes:jpg,jpeg,png|max:2048` for license). |
| 5   |                                                                                   | System stores the uploaded license image in the public `licenses` disk, generating a stored path.                                                                                                        |
| 6   |                                                                                   | System creates a **User** record with the supplied email, a bcrypt‑hashed password, and sets `email_verified_at` to the current timestamp (email considered verified).                               |
| 7   |                                                                                   | System creates a linked **Driver** record associated with the new user, persisting the name, contact information, and the stored license‑image path.                                                   |
| 8   |                                                                                   | If both User and Driver records are created successfully, the System assigns the **driver** role to the user and fires a `Registered` event.                                                       |
| 9   |                                                                                   | System redirects the guest to the login page (`/login`) with a success message: *“Driver registration submitted. Please wait for admin approval before signing in.”*                                    |

---

## Alternate Flow

**A1 – Guest wants to commute but has to register as driver first**
1. Guest attempts to access a driver‑only page (e.g., `/map` while unauthenticated).
2. System redirects the guest to the login page.
3. Guest follows the "Register as Driver" link from the login/registration page and proceeds with the main flow.

**A2 – Resend Verification Email (not applicable)**
Because the driver registration marks the email as verified immediately (`email_verified_at = now`), no separate verification email is sent. The flow does not include a resend‑verification step.

**A3 – Duplicate License Image**
If the guest re‑uploads the same license image (or a different one) during registration, the system treats each submission as a new file and stores it under a unique name; no deduplication is performed.

---

## Postconditions

1. A new **User** record exists in the `users` table with:
   - `email` as supplied,
   - `password` stored as a bcrypt hash,
   - `email_verified_at` set (email considered verified),
   - no roles assigned yet (role assigned at step 8).
2. A new **Driver** record exists in the `drivers` table linked to the user via `user_id`, containing:
   - `name`,
   - `contact_info`,
   - `license_image_path` (relative to the public disk),
   - `license_image_data` and `license_image_mime` set to `NULL`.
3. The user has been assigned the **driver** role (via `$user->assignRole('driver')`).
4. A `Registered` event has been fired (listeners may include email notifications, though none are configured for drivers in the current codebase).
5. The guest’s session remains unauthenticated; they are redirected to the login page and must sign in after admin approval.
6. The driver **cannot** sign in until an admin changes `is_approved` to `1` (see UCN_SC_E012 Manage Drivers). Attempting to sign in before approval results in a redirect with a “pending” flag and a message indicating awaiting approval.

---

## Exceptions

**E1 – Email Already Registered**
The email entered matches an existing account (`unique:users,email`). System displays *"The email has already been taken."* and the form retains the entered email address.

**E2 – Invalid Email Format**
The email is missing, malformed, or not a valid domain. System displays *"The email field must be a valid email address."*

**E3 – Weak Password**
The password does not meet the minimum security requirement (less than 8 characters). System displays *"The password field must be at least 8 characters."*

**E4 – Passwords Do Not Match**
The password and confirm‑password fields differ. System displays *"The password confirmation does not match."*

**E5 – Terms and Conditions Not Accepted**
The guest submits without checking the agreement box. System displays *"The terms field must be accepted."*

**E6 – Invalid License Image**
The uploaded file is not an image, or its MIME type is not JPG/JPEG/PNG, or its size exceeds 2 MB. System displays the corresponding validation message (e.g., *"The license image must be an image of type jpg, jpeg, png."*).

**E7 – Database Error**
The System fails to insert the user and/or driver record (e.g., due to a unique‑constraint violation on email after a race condition, or a general PDO exception). System displays *"A system error occurred while creating your driver account. Please try again later."* and cancels the registration (no partial records are left; the transaction is rolled back implicitly by Laravel’s model creation).

**E8 – File‑Storage Error**
The license image cannot be written to the `licenses` disk (e.g., insufficient permissions, disk quota). System treats this as a database error and shows the generic message from **E7**.

**E9 – Too Many Attempts (Rate Limiting)**
The guest submits more than **6 registration attempts per minute** (same throttle as commuter registration). System blocks the request (HTTP 429).

---

## Known Gaps / Notes

- **Wallet creation:** Unlike commuter registration (UCN_SC_E002), the driver registration flow does **not** automatically create a `Wallet` record. In the current codebase a wallet appears to be created lazily when the driver first accesses payment‑related pages (via `Wallet::firstOrCreate`). This behaviour should be documented or, if desired, a wallet could be created at registration time for symmetry.
- **Email verification:** The driver’s email is marked as verified immediately (`email_verified_at = now`). Consequently, no verification email is sent, and the driver does not need to click a verification link to access the system (aside from awaiting admin approval).
- **Admin approval gate:** Although the driver can log in with their credentials after registration, the `map` method (and other driver‑guarded routes) checks the driver’s `is_approved` flag. Unapproved drivers are logged out and redirected to the login page with a warning, effectively preventing them from using driver‑only features until an admin approves the registration (see UCN_SC_E012).
- **License image handling:** The stored license image path is saved, but the raw image data and MIME type are left as `NULL`. If the system ever needs to display or process the license image, it relies solely on the stored file path.
- **Rate‑limiting shares the commuter registration throttle** (`throttle:6,1` inside the `guest` middleware group), which limits both `/register` and `/register/driver` endpoints combined.
- **Localization / validation messages:** Validation messages follow Laravel’s defaults; custom messages are not defined in the request validation (they rely on the default translations).

---

## Implementation References

| Element | Location |
|---|---|
| Routes | `routes/web.php` — `GET /register/driver` (`driver.register.page`), `POST /register/driver` (`driver.register`) (both inside `guest` middleware with `throttle:6,1`) |
| Controller | `app/Http/Controllers/DriverController.php::create()`, `store()` |
| View | `resources/views/auth/driver/register.blade.php` |
| File storage | `public/disks/licenses/` (configured in `config/filesystems.php` as the `licenses` disk) |
| Role assignment | `$user->assignRole('driver')` (uses Laravel‑permission/Spatie package) |
| Events | `Illuminate\Auth\Events\Registered` fired after successful creation |
| Redirect target | `route('login')` → `/login` |
| Related UCNs | UCN_SC_E002 (Commuter Create Account), UCN_SC_E012 (Manage Drivers – approval/reject flow), UCN_SC_E009/E010 (Clock In/Out), UCN_SC_E015 (Manage Vehicles – vehicle assignment) |