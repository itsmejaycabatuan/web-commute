<?php

namespace App\Http\Controllers;

use App\Models\Fare;
use App\Models\FareRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FareController extends Controller
{   
    public function index() {
        $fares = Fare::get();
        $latestFare = Fare::get()->last();
        $latestFareId = $latestFare->id;
        $rates = FareRate::where('fare_id', $latestFareId)->get();

        return view('fares.index',[
            'fares' => $fares,
            'rates' => $rates
        ]);
    }

    public function view($id) {
        $rates = FareRate::where('fare_id', $id)->get();

        if(!$rates) {
            return back()->with('error', 'Rates not found.');
        }

        // $path = $fare->location;
        // $pythonPath = resource_path() . '/scripts/extractPdf.py ';
        // $fullPath = storage_path('app/' . $path);

        // $result = shell_exec(base_path('/venv/bin/python3 ') . $pythonPath . $fullPath);
        // $output = json_decode($result, true);

        // $rates = [];

        // for($i = 1; $i <= 25; $i++) {
        //     $rates[] = [
        //         'km' => $output[$i]['km'],
        //         'regular' => $output[$i]['regular'],
        //         'discount' => $output[$i]['discount']
        //     ];
        // }

        // for($i = 27; $i < 52; $i++) {
        //     $rates[] = [
        //         'km' => $output[$i]['km'],
        //         'regular' => $output[$i]['regular'],
        //         'discount' => $output[$i]['discount']
        //     ];
        // }

        // dd($rates);
        return view('fares.view', [
            'rates' => $rates
        ]);
    }

    public function upload(Request $request) {
        $validated = $request->validate([
            'fare' => 'required|file',
        ]);

        if(!$validated) {
            return back()->with('error','File upload failed.');
        }

        $path = $request->file('fare')->store('storage');

        DB::transaction(function() use ($path) {
            $fare = Fare::create([
                'location' => $path,
            ]);

            $pythonPath = resource_path() . '/scripts/extractPdf.py ';
            $fullPath = storage_path('app/' . $path);

            $result = shell_exec(base_path('/venv/bin/python3 ') . $pythonPath . $fullPath);
            $output = json_decode($result, true);

            $rates = [];

            for($i = 1; $i <= 25; $i++) {
                $rates[] = [
                    'fare_id' => $fare->id,
                    'km' => $output[$i]['km'],
                    'regular' => $output[$i]['regular'],
                    'discount' => $output[$i]['discount'],
                    'created_at' => now(),
                    'updated_at'=> now(),
                ];
            }

            for($i = 27; $i < 52; $i++) {
                $rates[] = [
                    'fare_id' => $fare->id,
                    'km' => $output[$i]['km'],
                    'regular' => $output[$i]['regular'],
                    'discount' => $output[$i]['discount'],
                    'created_at' => now(),
                    'updated_at'=> now(),
                ];
            }

            DB::table('fare_rates')->insert($rates);
        });
        
        return back()->with('success','File uploaded successfully!');
    }

    public function delete($id) {
        $fare = Fare::find($id);

        if(!$fare) {
            return back()->with('error','File delete failed.');
        }

        Fare::destroy($id);
        return back()->with('success','File deleted successfully!');
    }
}
