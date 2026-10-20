<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DriverApprovalController extends Controller
{
    /** The registration screens' wording, so the admin modals answer the same way. */
    private function messages(): array
    {
        return [
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password field must be at least 8 characters.',
            'contact_info.regex' => 'Enter a valid Philippine mobile number (e.g. 917 123 4567).',
            'license_image.mimes' => 'The license image must be an image of type jpg, jpeg, png.',
            'license_image.max' => 'The license image may not be greater than 2 MB.',
        ];
    }

    public function index()
    {
        $drivers = Driver::join('users', 'drivers.user_id', '=', 'users.id')
            ->select([
                'drivers.id',
                'drivers.user_id',
                'drivers.driver_code',
                'drivers.name',
                'drivers.license_number',
                'drivers.license_code',
                'drivers.license_image_path',
                'drivers.license_status',
                'drivers.is_approved',
                'drivers.is_rejected',
                'drivers.is_suspended',
                'drivers.suspended_at',
                'drivers.suspension_reason',
                'drivers.status',
                'drivers.expiration_date',
                'drivers.contact_info',
                'drivers.created_at',
                'users.email',
            ])
            ->addSelect(DB::raw('CASE WHEN drivers.license_image_data IS NOT NULL AND LENGTH(drivers.license_image_data) > 0 THEN 1 ELSE 0 END as has_license_in_db'))
            ->addSelect(DB::raw('(SELECT COUNT(*) FROM vehicles WHERE vehicles.driver_id = drivers.id) as vehicles_count'))
            ->orderByDesc('drivers.created_at')
            ->get()
            // Pending registrations first, then rejected, then approved drivers.
            ->sortBy(fn (Driver $d) => $d->isPending() ? 0 : ($d->is_rejected ? 1 : 2))
            ->values();

        return view('admin.drivers.index', compact('drivers'));
    }

    public function create()
    {
        return view('admin.drivers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'license_number' => 'required|string|max:255|unique:drivers,license_number',
            'license_code' => 'required|string|max:255',
            'expiration_date' => 'required|string|max:255',
            'contact_info' => ['required', 'string', 'max:255', 'regex:/^(?:\+?63|0)?\s?9\d{2}\s?\d{3}\s?\d{4}$/'],
            'license_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], $this->messages());

        $path = $request->file('license_image')->store('licenses', 'public');

        // E4 — the user, driver record and role are written together or not at
        // all; the stored licence image is removed again when that fails.
        try {
            DB::transaction(function () use ($request, $validated, $path) {
                $user = User::create([
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'email_verified_at' => now(),
                ]);

                $user->driver()->create([
                    'name' => $request->name,
                    'license_code' => $request->license_code,
                    'contact_info' => $request->contact_info,
                    'license_image_path' => $path,
                    'license_image_data' => null,
                    'license_image_mime' => null,
                    'expiration_date' => $validated['expiration_date'],
                    'license_number' => $validated['license_number'],
                    'is_approved' => true,
                ]);

                $user->syncRoles(['driver']);
            });
        } catch (QueryException $e) {
            Storage::disk('public')->delete($path);
            report($e);

            return back()->withInput()->with('error', 'A database error occurred. The driver account was not created.');
        }

        return redirect()
            ->route('drivers.index')
            ->with('success', 'Driver account created.');
    }

    public function edit(string $id)
    {
        $driver = Driver::with('user')->find($id);

        if (! $driver) {
            return back()->with('error', 'Driver not found.');
        }

        return view('admin.drivers.edit', ['driver' => $driver]);
    }

    public function update(Request $request, string $id)
    {
        $driver = Driver::find($id);

        if (! $driver) {
            return back()->with('error', 'Driver not found.');
        }

        // A registration that is still waiting for approval can only be approved
        // or rejected, not edited.
        if ($driver->isPending()) {
            return back()->with('error', 'This driver is still pending approval. Approve or reject the registration first.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'license_number' => 'required|string|max:255|unique:drivers,license_number,'.$driver->id,
            'license_code' => 'required|string|max:255',
            'expiration_date' => 'required|date',
            'contact_info' => ['required', 'string', 'max:255', 'regex:/^(?:\+?63|0)?\s?9\d{2}\s?\d{3}\s?\d{4}$/'],
            'is_approved' => 'in:0,1',
            'is_rejected' => 'in:0,1',
            // E7 — the replacement licence image must be a JPG / PNG of at most 2 MB.
            'license_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], $this->messages());

        $oldPath = $driver->license_image_path;
        $newPath = null;

        try {
            // Handle license image upload if provided
            if ($request->hasFile('license_image')) {
                $newPath = $request->file('license_image')->store('licenses', 'public');
                $driver->license_image_path = $newPath;
                $driver->license_image_data = null;
                $driver->license_image_mime = null;
            }

            $driver->update(Arr::except($validated, ['license_image']));
        } catch (QueryException $e) {
            // E4 — nothing is written; the freshly stored image is discarded.
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            report($e);

            return back()->withInput()->with('error', 'A database error occurred. The driver was not updated.');
        }

        // The old image is only removed once the new one is safely saved.
        if ($newPath && $oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('drivers.index')
            ->with('success', 'Driver updated.');
    }

    public function destroy(string $id)
    {
        $driver = Driver::find($id);

        if (! $driver) {
            return back()->with('error', 'Driver not found.');
        }

        // E8 — a driver holding a vehicle cannot be removed: release the vehicle
        // first so it does not vanish from the fleet along with the account.
        if ($driver->vehicle()->exists()) {
            return back()->with('error', 'This driver is still assigned to a vehicle. Unassign the vehicle first.');
        }

        $user = User::find($driver->user_id);

        // E4 — both rows go together or neither does.
        try {
            DB::transaction(function () use ($user, $driver) {
                $user?->delete();
                $driver->delete();
            });
        } catch (QueryException $e) {
            report($e);

            return back()->with('error', 'A database error occurred. The driver was not removed.');
        }

        return redirect()
            ->route('drivers.index')
            ->with('success', 'Driver removed.');
    }

    /**
     * Alternate flow — suspend / reactivate a driver. A suspended driver cannot
     * sign in, clock in or broadcast a position, and is detached from any vehicle
     * they were assigned.
     */
    public function toggleSuspension(string $id, Request $request)
    {
        activity()->event('Toggledriversuspension')->log('Action performed: toggleSuspension');

        $driver = Driver::find($id);

        if (! $driver) {
            return back()->with('error', 'Driver not found.');
        }

        // Only an approved driver can be suspended; a pending or rejected
        // registration has nothing to suspend.
        if (! $driver->isSuspended() && ! $driver->is_approved) {
            return back()->with(
                'error',
                $driver->isPending()
                    ? 'This driver is still pending approval. Approve or reject the registration first.'
                    : 'Only an approved driver can be suspended.'
            );
        }

        $validated = $request->validate([
            'suspension_reason' => 'nullable|string|max:255',
        ]);

        if ($driver->isSuspended()) {
            $driver->unsuspend();

            return redirect()
                ->route('drivers.index')
                ->with('success', 'Driver reactivated.');
        }

        $driver->suspend($validated['suspension_reason'] ?? null);

        activity()->causedBy(auth()->user())
            ->event('Suspend Driver')
            ->log('Suspended driver: '.($driver->user?->email ?? $driver->id));

        return redirect()
            ->route('drivers.index')
            ->with('success', 'Driver suspended. They can no longer sign in.');
    }

    public function approve(Request $request, string $user)
    {
        $driver = Driver::find($user);

        if (! $driver) {
            return redirect()
                ->route('drivers.index')
                ->with('error', 'Driver not found.');
        }

        // E2 — the license number must be unique across drivers.
        $validated = $request->validate([
            'license_number' => 'required|string|max:255|unique:drivers,license_number,'.$driver->id,
            'license_code' => 'required|string|max:255',
            'expiration_date' => 'required|date',
        ]);

        if ($driver->is_approved) {
            return redirect()
                ->route('drivers.index')
                ->with('error', 'This driver is already approved.');
        }

        try {
            $driver->update([
                'is_approved' => 1,
                'is_rejected' => 0,
                'license_number' => $validated['license_number'],
                'license_code' => $validated['license_code'],
                'expiration_date' => $validated['expiration_date'],
                'driver_code' => $driver->driver_code ?: Driver::nextDriverCode(),
            ]);
        } catch (QueryException $e) {
            report($e);

            return redirect()
                ->route('drivers.index')
                ->with('error', 'A database error occurred. The driver was not approved.');
        }

        return redirect()
            ->route('drivers.index')
            ->with('success', 'Driver approved. They can sign in now.');
    }

    // public function unapprove(Request $request, User $user)
    // {
    //     if (! $user->hasRole('driver')) {
    //         abort(404);
    //     }

    //     if ($user->driver_approval_status !== 'approved') {
    //         return redirect()
    //             ->route('admin.drivers.index')
    //             ->with('error', 'Only approved drivers can be set back to pending.');
    //     }

    //     $user->update(['driver_approval_status' => 'pending']);

    //     return redirect()
    //         ->route('admin.drivers.index')
    //         ->with('success', 'Driver set to pending. They cannot sign in until approved again.');
    // }

    public function reject(string $id, Request $request)
    {
        $driver = Driver::find($id);

        if (! $driver) {
            return back()->with('error', 'Driver not found.');
        }

        if ((bool) $driver->is_rejected) {
            return redirect()
                ->route('drivers.index')
                ->with('error', 'This driver is already rejected.');
        }

        try {
            $isRejected = $driver->update(['is_rejected' => 1, 'is_approved' => 0]);
        } catch (QueryException $e) {
            report($e);
            $isRejected = false;
        }

        if (! $isRejected) {
            return back()->with('error', 'Error rejecting driver');
        }

        return redirect()
            ->route('drivers.index')
            ->with('success', 'Driver registration rejected. They cannot sign in until you approve them again.');
    }

    /**
     * Serve the license image. Prefers DB (base64); falls back to disk for older uploads.
     * Use ?download=1 to force download. ?embed=1 returns minimal HTML with img (alternative viewer).
     */
    public function showLicense(Request $request, User $user): BinaryFileResponse|Response
    {

        if (! $user->hasRole('driver')) {
            abort(404);
        }

        if (filled($user->license_image_data)) {
            $raw = (string) $user->license_image_data;
            if (str_starts_with($raw, 'data:')) {
                $comma = strpos($raw, ',');
                if ($comma !== false) {
                    $raw = substr($raw, $comma + 1);
                }
            }
            $raw = preg_replace('/\s+/', '', $raw);
            $binary = base64_decode($raw, true);
            if ($binary === false || $binary === '') {
                $binary = base64_decode($raw, false);
            }
            if ($binary === false || $binary === '') {
                abort(404);
            }

            $sniffed = $this->detectImageMimeFromBinary($binary);
            $mime = $sniffed ?? ($user->license_image_mime ?: 'image/jpeg');
            if (! str_starts_with((string) $mime, 'image/')) {
                $mime = $sniffed ?? 'image/jpeg';
            }

            if ($request->boolean('embed')) {
                return response()->view('admin.drivers.license-embed', [
                    'imageUrl' => $this->licenseAbsoluteUrl($request, $user),
                    'title' => 'License — ' . $user->email,
                ]);
            }

            $ext = $this->licenseFileExtension($mime);

            if ($request->boolean('download')) {
                return $this->rawBinaryResponse($binary, $mime, true, $user->id, $ext);
            }

            return $this->rawBinaryResponse($binary, $mime, false, $user->id, $ext);
        }

        $relative = $this->resolveLicenseStoragePath($user->license_image_path);
        if ($relative === null) {
            abort(404);
        }

        $full = Storage::disk('public')->path($relative);

        // We use Storage::exists() to be safe, but Storage::path() should work
        if (! Storage::disk('public')->exists($relative)) {
            abort(404);
        }

        if ($request->boolean('embed')) {
            return response()->view('admin.drivers.license-embed', [
                'imageUrl' => $this->licenseAbsoluteUrl($request, $user),
                'title' => 'License — ' . $user->email,
            ]);
        }

        if ($request->boolean('download')) {
            return response()->download($full, 'license-' . $user->id . '-' . basename($full));
        }

        $mime = @mime_content_type($full) ?: 'application/octet-stream';
        if (! str_starts_with($mime, 'image/')) {
            $head = $this->readFileHead($full, 32);
            if ($head !== null) {
                $sniffed = $this->detectImageMimeFromBinary($head);
                if ($sniffed !== null) {
                    $mime = $sniffed;
                }
            }
        }

        return response()->file($full, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($full) . '"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Full URL to the license image using the current request host + base path (fixes XAMPP subfolders and 127.0.0.1 vs localhost).
     */
    private function licenseAbsoluteUrl(Request $request, User $user): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/')
            . rtrim($request->getBasePath(), '/')
            . route('drivers.license', $user, false);
    }

    /**
     * Raw image bytes (avoids encoding issues with response() helper on some setups).
     */
    private function rawBinaryResponse(string $binary, string $mime, bool $asDownload, int $userId, string $ext): Response
    {
        $filename = 'license-' . $userId . '.' . $ext;
        $disp = $asDownload ? 'attachment' : 'inline';

        return new Response($binary, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) strlen($binary),
            'Content-Disposition' => $disp . '; filename="' . $filename . '"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function readFileHead(string $path, int $bytes): ?string
    {
        $h = @fopen($path, 'rb');
        if ($h === false) {
            return null;
        }
        $data = fread($h, $bytes);
        fclose($h);

        return ($data !== false && $data !== '') ? $data : null;
    }

    /**
     * Detect image/* from file header so browsers display downloads even when DB mime is wrong.
     */
    private function detectImageMimeFromBinary(string $binary): ?string
    {
        if ($binary === '') {
            return null;
        }

        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }
        if (str_starts_with($binary, 'GIF87a') || str_starts_with($binary, 'GIF89a')) {
            return 'image/gif';
        }
        if (strlen($binary) >= 12 && str_starts_with($binary, 'RIFF') && substr($binary, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        return null;
    }

    private function licenseFileExtension(string $mime): string
    {
        return match (true) {
            str_contains($mime, 'png') => 'png',
            str_contains($mime, 'gif') => 'gif',
            str_contains($mime, 'webp') => 'webp',
            default => 'jpg',
        };
    }

    private function assertDriver(User $user): void
    {
        if (! $user->hasRole('driver')) {
            abort(404);
        }
    }

    private function deleteStoredLicense(?string $raw): void
    {
        $relative = $this->resolveLicenseStoragePath($raw);
        if ($relative !== null && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }

    /**
     * @return non-falsy-string|null path relative to storage/app/public
     */
    private function resolveLicenseStoragePath(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $path = str_replace('\\', '/', trim($raw));
        if (str_contains($path, '..')) {
            return null;
        }

        $path = ltrim($path, '/');

        $prefixes = [
            'storage/app/public/',
            'app/public/',
            'public/storage/',
            'storage/',
        ];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));

                break;
            }
        }

        $full = storage_path('app/public/' . $path);
        if (is_file($full)) {
            return $path;
        }

        $rawNormalized = ltrim(str_replace('\\', '/', $raw), '/');
        if (Storage::disk('public')->exists($rawNormalized)) {
            return $rawNormalized;
        }

        $basename = basename($path);
        if ($basename !== '' && $basename !== '.' && $basename !== '..') {
            $tryLicenses = 'licenses/' . $basename;
            if (is_file(storage_path('app/public/' . $tryLicenses))) {
                return $tryLicenses;
            }
        }

        return null;
    }
}
