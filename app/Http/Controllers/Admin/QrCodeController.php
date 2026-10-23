<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

/**
 * The Admin's QR code page: generate the codes that go up inside the PUJs, print them, switch them
 * off, delete them. A commuter has to scan one of these before paying a fare (see RideVerificationController).
 */
class QrCodeController extends Controller
{
    public function index()
    {
        $codes = QrCode::withCount('scans')
            ->latest()
            ->get()
            ->map(fn (QrCode $c) => [
                'id' => $c->id,
                'name' => $c->name(),
                'is_active' => $c->is_active,
                'scans' => $c->scans_count,
                'created' => $c->created_at->format('M d, Y'),
                'scan_url' => $c->scanUrl(),
                'print_url' => route('qr-codes.show', $c->id),
                'toggle_url' => route('qr-codes.toggle', $c->id),
                'delete_url' => route('qr-codes.destroy', $c->id),
            ]);

        return view('admin.qr-codes.index', compact('codes'));
    }

    /** One click, one new code. */
    public function store(Request $request)
    {
        try {
            $code = QrCode::create(['created_by' => $request->user()->id]);
        } catch (QueryException $e) {
            report($e);

            return back()->with('error', 'A database error occurred. The QR code was not created.');
        }

        return back()->with('success', $code->name().' created.');
    }

    /** The printable page: one big code with its label. */
    public function show(QrCode $qr)
    {
        return view('admin.qr-codes.print', ['code' => $qr]);
    }

    public function toggle(QrCode $qr)
    {
        $qr->update(['is_active' => ! $qr->is_active]);

        return back()->with('success', $qr->is_active ? 'QR code switched on.' : 'QR code switched off. Scanning it no longer verifies a ride.');
    }

    public function destroy(QrCode $qr)
    {
        try {
            $qr->delete();
        } catch (QueryException $e) {
            report($e);

            return back()->with('error', 'A database error occurred. The QR code was not deleted.');
        }

        return back()->with('success', 'QR code deleted.');
    }
}
