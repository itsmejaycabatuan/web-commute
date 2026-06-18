<?php

namespace App\Http\Controllers;

class DriverManagerController extends Controller
{
    public function timeKeeping()
    {
        return view('driver-manager.time-keeping');
    }

    public function violationsLog()
    {
        return view('driver-manager.violations-log');
    }

    public function violationCodes()
    {
        return view('driver-manager.violation-codes');
    }
}
