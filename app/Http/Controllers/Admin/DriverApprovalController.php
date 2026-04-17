<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DriverApprovalController extends Controller
{
    public function index()
    {
        $drivers = User::role('driver')
            ->select([
                'users.id',
                'users.email',
                'users.license_number',
                'users.license_code',
                'users.license_image_path',
                'users.driver_approval_status',
                'users.created_at',
            ])
            ->addSelect(DB::raw('CASE WHEN users.license_image_data IS NOT NULL AND LENGTH(users.license_image_data) > 0 THEN 1 ELSE 0 END as has_license_in_db'))
            ->orderByDesc('created_at')
            ->get()
            ->sortBy(fn (User $u) => match ($u->driver_approval_status) {
                'pending' => 0,
                'rejected' => 1,
                'approved' => 2,
                default => 3,
            })
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
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'license_number' => 'required|string|max:255',
            'license_code' => 'required|string|max:255',
            'license_image' => 'required|image|mimes:jpg,jpeg,png|max:4096',
            'driver_approval_status' => 'required|in:pending,approved,rejected',
        ]);

        $path = $request->file('license_image')->store('licenses', 'public');

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'license_number' => $validated['license_number'],
            'license_code' => $validated['license_code'],
            'license_image_path' => $path,
            'license_image_data' => null,
            'license_image_mime' => null,
            'driver_approval_status' => $validated['driver_approval_status'],
            'email_verified_at' => now(),
        ]);

        $user->syncRoles(['driver']);

        return redirect()
            ->route('admin.drivers.index')
            ->with('success', 'Driver account created.');
    }

    public function edit(User $user)
    {
        $this->assertDriver($user);

        return view('admin.drivers.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->assertDriver($user);

        $validated = $request->validate([
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'license_number' => 'required|string|max:255',
            'license_code' => 'required|string|max:255',
            'license_image' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'driver_approval_status' => 'required|in:pending,approved,rejected',
        ]);

        if ($request->hasFile('license_image')) {
            $this->deleteStoredLicense($user->license_image_path);
            $user->license_image_path = $request->file('license_image')->store('licenses', 'public');
            $user->license_image_data = null;
            $user->license_image_mime = null;
        }

        $user->email = $validated['email'];
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->license_number = $validated['license_number'];
        $user->license_code = $validated['license_code'];
        $user->driver_approval_status = $validated['driver_approval_status'];
        $user->save();

        return redirect()
            ->route('admin.drivers.index')
            ->with('success', 'Driver updated.');
    }

    public function destroy(User $user)
    {
        $this->assertDriver($user);

        $this->deleteStoredLicense($user->license_image_path);
        $user->delete();

        return redirect()
            ->route('admin.drivers.index')
            ->with('success', 'Driver removed.');
    }

    public function approve(Request $request, User $user)
    {
        if (! $user->hasRole('driver')) {
            abort(404);
        }

        if ($user->driver_approval_status === 'approved') {
            return redirect()
                ->route('admin.drivers.index')
                ->with('error', 'This driver is already approved.');
        }

        if (! in_array($user->driver_approval_status, ['pending', 'rejected'], true)) {
            return redirect()
                ->route('admin.drivers.index')
                ->with('error', 'Only pending or rejected drivers can be approved.');
        }

        $user->update(['driver_approval_status' => 'approved']);

        return redirect()
            ->route('admin.drivers.index')
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

    public function reject(Request $request, User $user)
    {
        if (! $user->hasRole('driver')) {
            abort(404);
        }

        if ($user->driver_approval_status === 'rejected') {
            return redirect()
                ->route('admin.drivers.index')
                ->with('error', 'This driver is already rejected.');
        }

        $user->update(['driver_approval_status' => 'rejected']);

        return redirect()
            ->route('admin.drivers.index')
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
                    'title' => 'License — '.$user->email,
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

        $full = storage_path('app/public/'.$relative);
        if (! is_file($full)) {
            abort(404);
        }

        if ($request->boolean('embed')) {
            return response()->view('admin.drivers.license-embed', [
                'imageUrl' => $this->licenseAbsoluteUrl($request, $user),
                'title' => 'License — '.$user->email,
            ]);
        }

        if ($request->boolean('download')) {
            return response()->download($full, 'license-'.$user->id.'-'.basename($full));
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
            'Content-Disposition' => 'inline; filename="'.basename($full).'"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Full URL to the license image using the current request host + base path (fixes XAMPP subfolders and 127.0.0.1 vs localhost).
     */
    private function licenseAbsoluteUrl(Request $request, User $user): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/')
            .rtrim($request->getBasePath(), '/')
            .route('admin.drivers.license', $user, false);
    }

    /**
     * Raw image bytes (avoids encoding issues with response() helper on some setups).
     */
    private function rawBinaryResponse(string $binary, string $mime, bool $asDownload, int $userId, string $ext): Response
    {
        $filename = 'license-'.$userId.'.'.$ext;
        $disp = $asDownload ? 'attachment' : 'inline';

        return new Response($binary, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) strlen($binary),
            'Content-Disposition' => $disp.'; filename="'.$filename.'"',
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

        $full = storage_path('app/public/'.$path);
        if (is_file($full)) {
            return $path;
        }

        $rawNormalized = ltrim(str_replace('\\', '/', $raw), '/');
        if (Storage::disk('public')->exists($rawNormalized)) {
            return $rawNormalized;
        }

        $basename = basename($path);
        if ($basename !== '' && $basename !== '.' && $basename !== '..') {
            $tryLicenses = 'licenses/'.$basename;
            if (is_file(storage_path('app/public/'.$tryLicenses))) {
                return $tryLicenses;
            }
        }

        return null;
    }
}
