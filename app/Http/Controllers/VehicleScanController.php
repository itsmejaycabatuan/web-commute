<?php

namespace App\Http\Controllers;

use App\Models\VehicleScanSession;
use App\Services\VehicleScan\TesseractClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The desktop half of "scan the registration papers with your phone" (the Add Vehicle form).
 * The Maintenance Manager's browser starts a session, shows its link as a QR code and polls for the result.
 */
class VehicleScanController extends Controller
{
    /** Can this server read photos at all? Hides the button when Tesseract is not installed or the feature is off. */
    public static function available(): bool
    {
        return (bool) config('vehicle_scan.enabled') && app(TesseractClient::class)->available();
    }

    public function store(Request $request)
    {
        if (! self::available()) {
            return response()->json(['message' => 'Scanning is not available on this server.'], 503);
        }

        // One live scan per person: starting another closes the first.
        VehicleScanSession::where('user_id', $request->user()->id)
            ->whereIn('status', [VehicleScanSession::PENDING, VehicleScanSession::PROCESSING, VehicleScanSession::READY, VehicleScanSession::FAILED])
            ->get()->each(fn (VehicleScanSession $old) => $this->close($old, VehicleScanSession::CANCELLED));

        $session = VehicleScanSession::create([
            'token' => Str::random(40),
            'user_id' => $request->user()->id,
            'status' => VehicleScanSession::PENDING,
            'expires_at' => now()->addMinutes((int) config('vehicle_scan.ttl_minutes', 15)),
        ]);

        return response()->json($this->payload($session), 201);
    }

    public function show(Request $request, VehicleScanSession $session)
    {
        $this->owns($request, $session);

        return response()->json($this->payload($session));
    }

    /** One of the photographed pages, so the manager can compare it with the values (owner only, never cached). */
    public function page(Request $request, VehicleScanSession $session, int $n)
    {
        $this->owns($request, $session);

        $path = ($session->files ?? [])[$n] ?? null;
        abort_unless($path && Storage::disk(VehicleScanSession::DISK)->exists($path), 404);

        // A registration paper must never be kept by a proxy or the browser cache.
        $response = response()->file(Storage::disk(VehicleScanSession::DISK)->path($path));
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    /** The manager closed the form or started over: the photos go now. */
    public function destroy(Request $request, VehicleScanSession $session)
    {
        $this->owns($request, $session);

        if ($session->status !== VehicleScanSession::CONSUMED) {
            $this->close($session, VehicleScanSession::CANCELLED);
        }

        return response()->json(['status' => $session->fresh()->status]);
    }

    private function owns(Request $request, VehicleScanSession $session): void
    {
        abort_unless($session->user_id === $request->user()->id, 404);
    }

    private function close(VehicleScanSession $session, string $status): void
    {
        $session->deleteFiles();
        $session->update(['status' => $status, 'result' => null]);
    }

    private function payload(VehicleScanSession $session): array
    {
        $status = $session->visibleStatus();

        // An expired session's photos are not waiting for anyone.
        if ($status === 'expired' && $session->files) {
            $session->deleteFiles();
        }

        return [
            'id' => $session->id,
            'status' => $status,
            'phone_url' => route('vehicle-scan.phone', $session->token),
            'expires_in' => max(0, now()->diffInSeconds($session->expires_at, false)),
            'error' => $status === VehicleScanSession::FAILED ? $session->error : null,
            'fields' => $status === VehicleScanSession::READY ? ($session->result['fields'] ?? []) : null,
            'warnings' => $status === VehicleScanSession::READY ? ($session->result['warnings'] ?? []) : null,
            'documents' => $status === VehicleScanSession::READY ? ($session->result['documents'] ?? []) : null,
            'pages' => $status === VehicleScanSession::READY
                ? collect($session->files ?? [])->keys()->map(fn ($n) => route('vehicle-scan.page', [$session->id, $n]))->all()
                : [],
        ];
    }
}
