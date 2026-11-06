<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Switches for the "Dev: map checks" panel that have to live on the server, because the rule they
 * relax is enforced there. Local installs only (see the `env.local` middleware on the route); every
 * reader of the switch checks the environment again, so it can never take effect elsewhere.
 */
class DevSwitchController extends Controller
{
    /** Pay a fare without scanning a ride QR code first (`QrScan::required()`). */
    public function skipQr(Request $request): JsonResponse
    {
        $skip = $request->boolean('skip');

        $request->session()->put('dev_skip_qr', $skip);

        return response()->json(['skip' => $skip]);
    }
}
