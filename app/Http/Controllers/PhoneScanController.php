<?php

namespace App\Http\Controllers;

use App\Models\VehicleScanSession;
use App\Services\VehicleScan\RegistrationDocumentReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * The phone half: opened from the QR code on the Maintenance Manager's screen. There is no sign-in on the phone:
 * the long random token in the link is the credential, it works for one scan session only, and it lets the phone
 * do exactly one thing, send photos to that session. The phone is never shown what was read.
 */
class PhoneScanController extends Controller
{
    public function show(string $token)
    {
        $session = $this->find($token);

        if (! $session || ! $session->acceptsUploads()) {
            return response()->view('vehicle-scan.phone', ['session' => null, 'token' => $token, 'max' => (int) config('vehicle_scan.max_files', 2)], 410);
        }

        return view('vehicle-scan.phone', ['session' => $session, 'token' => $token, 'max' => (int) config('vehicle_scan.max_files', 2)]);
    }

    public function upload(Request $request, string $token, RegistrationDocumentReader $reader)
    {
        $session = $this->find($token);

        if (! $session || ! $session->acceptsUploads()) {
            return $this->respond($request, 410, 'expired', 'This scan link has expired. Start again on your computer.');
        }

        $validator = Validator::make($request->all(), [
            'photos' => 'required|array|min:1|max:'.(int) config('vehicle_scan.max_files', 2),
            'photos.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:'.(int) config('vehicle_scan.max_file_kb', 12288),
        ], [
            'photos.required' => 'Choose a photo of the paper first.',
            'photos.max' => 'Send at most '.(int) config('vehicle_scan.max_files', 2).' photos: the Certificate of Registration and the Official Receipt.',
            'photos.*.image' => 'That file is not a photo.',
            'photos.*.mimes' => 'Use a JPG, PNG or WebP photo.',
            'photos.*.max' => 'That photo is too large. Take it again at a lower quality.',
        ]);

        if ($validator->fails()) {
            return $this->respond($request, 422, 'invalid', $validator->errors()->first());
        }

        // A retake replaces whatever this session held before.
        $session->deleteFiles();

        $paths = [];
        foreach ($request->file('photos') as $n => $file) {
            $paths[$n] = $file->storeAs($session->directory(), 'page-'.$n.'.'.$file->extension(), VehicleScanSession::DISK);
        }

        $session->update(['status' => VehicleScanSession::PROCESSING, 'files' => $paths, 'result' => null, 'error' => null, 'uploaded_at' => now()]);

        @set_time_limit(180);

        try {
            $result = $reader->read(array_map(fn ($p) => Storage::disk(VehicleScanSession::DISK)->path($p), $paths));
        } catch (\Throwable $e) {
            report($e);
            $session->deleteFiles();
            $session->update(['status' => VehicleScanSession::FAILED, 'error' => 'We could not read those photos. Take them again, flat and in good light.']);

            return $this->respond($request, 200, 'failed', $session->error);
        }

        if (! $result['fields']) {
            $message = $result['warnings'][0] ?? 'We could not read anything from those photos. Take them again, flat and in good light.';
            $session->deleteFiles();
            $session->update(['status' => VehicleScanSession::FAILED, 'error' => $message]);

            return $this->respond($request, 200, 'failed', $message);
        }

        // The manager now has a full window to compare the values with the papers and save.
        $session->update(['status' => VehicleScanSession::READY, 'result' => $result, 'expires_at' => now()->addMinutes((int) config('vehicle_scan.ttl_minutes', 15))]);

        return $this->respond($request, 200, 'ready', 'Done. Continue on your computer.');
    }

    private function find(string $token): ?VehicleScanSession
    {
        return strlen($token) === 40 ? VehicleScanSession::where('token', $token)->first() : null;
    }

    private function respond(Request $request, int $http, string $status, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => $status, 'message' => $message], $http);
        }

        return response()->view('vehicle-scan.phone-result', ['status' => $status, 'message' => $message], $http);
    }
}
