<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Events\PusherEvent;

class PusherController extends Controller
{
    public function index() {


        return view('test.pusher');
    }

    public function fireEvent() {
        event(new PusherEvent('LIBAT'));
        return response()->json([
            'message' => 'event sent'
        ]);
    }
}
