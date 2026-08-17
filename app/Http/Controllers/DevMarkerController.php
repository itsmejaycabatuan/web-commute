<?php

namespace App\Http\Controllers;

use App\Models\DevMarker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Pusher\Pusher;

class DevMarkerController extends Controller
{
    private function names()
    {
        return [
            'Juan Dela Cruz', 'Maria Santos', 'Ricardo Reyes', 'Ana Garcia',
            'Pedro Mendoza', 'Carmen Lim', 'Jose Rizal', 'Rosa Cruz',
            'Miguel Torres', 'Elena Flores', 'Antonio Villanueva', 'Grace Navarro',
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $existing = DB::table('dev_markers')->count();

        DevMarker::create([
            'user_id' => Auth::id(),
            'name' => $this->names()[($existing) % count($this->names())],
            'lat' => $request->lat,
            'lng' => $request->lng,
            'status' => 'active',
        ]);

        $this->broadcastMarkerUpdate();

        return back()->with('success', 'Dummy marker added.');
    }

    public function toggle($id)
    {
        $marker = DevMarker::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $marker->update([
            'status' => $marker->status === 'active' ? 'inactive' : 'active',
        ]);

        $this->broadcastMarkerUpdate();

        return back();
    }

    public function remove($id)
    {
        DevMarker::where('id', $id)->where('user_id', Auth::id())->delete();

        $this->broadcastMarkerUpdate();

        return back();
    }

    public function clear()
    {
        DevMarker::where('user_id', Auth::id())->delete();

        $this->broadcastMarkerUpdate();

        return back();
    }

    private function broadcastMarkerUpdate()
    {
        if (app()->environment('local')) {
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                ['cluster' => env('PUSHER_APP_CLUSTER'), 'useTLS' => true]
            );
            $pusher->trigger('dev-markers', 'marker-updated', []);
        }
    }
}
