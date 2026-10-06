<?php

namespace App\Http\Controllers;

use App\Models\Fare;
use App\Models\FareRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FareController extends Controller
{
    public function index()
    {
        activity()->event('Index')->log('Action performed: index');
        $fares = Fare::get();
        $latestFare = Fare::get()->last();
        $rates = FareRate::get();

        if ($latestFare) {
            $latestFareId = $latestFare->id;
            $rates = FareRate::where('fare_id', $latestFareId)->get();
        }

        return view('admin.fares.index', [
            'fares' => $fares,
            'rates' => $rates,
        ]);
    }

    public function view($id)
    {
        activity()->event('View')->log('Action performed: view');
        $rates = FareRate::where('fare_id', $id)->get();

        if (! $rates) {
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
        return view('admin.fares.view', [
            'rates' => $rates,
        ]);
    }

    public function upload(Request $request)
    {
        activity()->event('Upload')->log('Action performed: upload');
        $validated = $request->validate([
            'fare' => 'required|file|mimes:pdf',
        ]);

        $path = $request->file('fare')->store('fares');

        DB::transaction(function () use ($path) {
            $fare = Fare::create([
                'location' => $path,
            ]);

            $isWindows = PHP_OS_FAMILY === 'Windows';

            $pythonPath = resource_path('scripts/extractPdf.py');
            $fullPath = storage_path('app/' . $path);
            $python = $isWindows
                ? base_path('venv\Scripts\python.exe')
                : base_path('venv/bin/python3');

            // Quote all paths — handles spaces in directory names
            $command = sprintf(
                '"%s" "%s" "%s" 2>&1',
                $python,
                $pythonPath,
                $fullPath
            );

            $result = shell_exec($command);

            if ($result === null) {
                throw new \RuntimeException('Python script failed to execute.');
            }

            $output = json_decode($result, true);

            if (! is_array($output)) {
                throw new \RuntimeException('Python script returned invalid JSON: ' . $result);
            }

            $rates = [];

            for ($i = 1; $i <= 25; $i++) {
                $rates[] = [
                    'fare_id' => $fare->id,
                    'km' => $output[$i]['km'],
                    'regular' => $output[$i]['regular'],
                    'discount' => $output[$i]['discount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            for ($i = 27; $i < 52; $i++) {
                $rates[] = [
                    'fare_id' => $fare->id,
                    'km' => $output[$i]['km'],
                    'regular' => $output[$i]['regular'],
                    'discount' => $output[$i]['discount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('fare_rates')->insert($rates);
        });

        return back()->with('success', 'File uploaded successfully!');
    }

    public function bulkUpdate(Request $request)
    {
        activity()->event('Bulkupdate')->log('Action performed: bulkUpdate');

        // E2 — Rates Empty: nothing was submitted to save.
        if (! $request->has('rates') || ! is_array($request->input('rates')) || $request->input('rates') === []) {
            return back()
                ->withInput()
                ->withErrors(['rates' => 'There are no rates to save.']);
        }

        // E4 — Rate Cannot be Zero or Negative.
        $validated = $request->validate([
            'rates' => ['required', 'array', 'min:1'],
            'rates.*.id' => ['nullable', 'integer'],
            'rates.*.regular' => ['required', 'numeric', 'min:0.01'],
            'rates.*.discount' => ['required', 'numeric', 'min:0.01'],
        ], [
            'rates.*.regular.min' => 'A fare rate must be greater than zero.',
            'rates.*.discount.min' => 'A discount rate must be greater than zero.',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['rates'] as $id => $data) {
                FareRate::where('id', $data['id'] ?? $id)->update([
                    'regular' => $data['regular'],
                    'discount' => $data['discount'],
                ]);
            }
        });

        return back()->with('success', 'Rates updated successfully');
    }
}
