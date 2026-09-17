<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Events\PusherEvent;

class PusherController extends Controller
{
    public function index() {
        activity()->event('Index')->log('Action performed: index');


        return view('test.pusher');
    }

    public function fireEvent() {
        activity()->event('Fireevent')->log('Action performed: fireEvent');
        event(new PusherEvent('LIBAT'));
        return response()->json([
            'message' => 'event sent'
        ]);
    }
}
