<?php

namespace App\Http\Controllers;

use App\Models\Fare;
use App\Models\FareRate;
use App\Support\Audit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class FareController extends Controller
{
    public function index()
    {
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
        $validated = $request->validate([
            'fare' => 'required|file|mimes:pdf',
        ], [
            // E5 — Document file is required (and must be a PDF).
            'fare.required' => 'A fare document file is required. Please select a PDF.',
            'fare.file' => 'A fare document file is required. Please select a PDF.',
            'fare.mimes' => 'The fare document must be a PDF file.',
        ]);

        $path = $request->file('fare')->store('fares');

        try {
            $fare = DB::transaction(function () use ($path) {
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

                return $fare;
            });
        } catch (QueryException|\RuntimeException $e) {
            // E3 — Database error (or an unreadable PDF): the transaction is rolled
            // back, the stored file is discarded and the Admin is told.
            Storage::delete($path);
            report($e);

            return back()->with('error', 'A database error occurred. The fare document was not saved.');
        }

        // The Fare row is audited by the model; the tiers are bulk-inserted with the query builder, so say so here.
        $rateRows = DB::table('fare_rates')->where('fare_id', $fare->id)->count();
        Audit::log(Audit::FARE, 'fare_guide_imported', 'Imported a fare guide with '.$rateRows.' rate rows', $fare, [
            'file' => $path,
            'rate_rows' => $rateRows,
        ]);

        return back()->with('success', 'File uploaded successfully!');
    }

    public function bulkUpdate(Request $request)
    {

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

        // E3 — Database error: the update is all-or-nothing and the Admin is told.
        $changes = [];

        try {
            DB::transaction(function () use ($validated, &$changes) {
                foreach ($validated['rates'] as $id => $data) {
                    $rateId = $data['id'] ?? $id;
                    $before = FareRate::find($rateId);

                    FareRate::where('id', $rateId)->update([
                        'regular' => $data['regular'],
                        'discount' => $data['discount'],
                    ]);

                    // Written with the query builder (one UPDATE per tier), so no model event fires:
                    // collect old → new here and log the whole edit as one entry below.
                    if ($before && ((float) $before->regular !== (float) $data['regular'] || (float) $before->discount !== (float) $data['discount'])) {
                        $changes[] = [
                            'km' => $before->km,
                            'regular' => ['old' => (float) $before->regular, 'new' => (float) $data['regular']],
                            'discount' => ['old' => (float) $before->discount, 'new' => (float) $data['discount']],
                        ];
                    }
                }
            });
        } catch (QueryException $e) {
            report($e);

            return back()->with('error', 'A database error occurred. The rates were not updated.');
        }

        if ($changes) {
            Audit::log(Audit::FARE, 'fare_rates_updated', 'Updated '.count($changes).' fare rate(s)', null, [
                'rows_changed' => count($changes),
                'changes' => $changes,
            ]);
        }

        return back()->with('success', 'Rates updated successfully');
    }
}
