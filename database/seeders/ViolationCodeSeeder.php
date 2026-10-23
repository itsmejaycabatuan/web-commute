<?php

namespace Database\Seeders;

use App\Models\ViolationCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Violation codes for the violations most commonly written up against
 * Philippine public utility jeepney (PUJ) drivers and operators.
 *
 * The amounts follow the LTO traffic schedule (RA 4136 and its fine
 * issuances), LTFRB/DOTr-LTO Joint Administrative Order 2014-01 (franchise and
 * conduct violations) and RA 10913 (anti-distracted driving). Fines for the
 * same violation differ between issuances and are revised from time to time,
 * so treat the figures as a starting schedule: the Driver Manager can edit any
 * of them under Violation Codes.
 *
 * The four columns are the 1st, 2nd and 3rd offense and the 4th-and-later
 * offense. Where the rule stops adding money after a point (for example
 * revocation of the licence or franchise), the last tier repeats.
 *
 * Running this REPLACES the whole table, so codes created earlier are removed
 * and, because violation logs belong to a code, so are their logged violations.
 */
class ViolationCodeSeeder extends Seeder
{
    /** [code, violation name, 1st, 2nd, 3rd, 4th+] */
    public const CODES = [
        ['UV01', 'Disregarding Traffic Signs / Signals', 1000, 1000, 1000, 1000],
        ['UV02', 'Reckless Driving', 2000, 10000, 10000, 10000],
        ['UV03', 'Using a Mobile Phone While Driving', 5000, 10000, 15000, 20000],
        ['UV04', 'Driving Without a Valid Driver\'s License', 3000, 3000, 3000, 3000],
        ['UV05', 'Unregistered or Expired Vehicle Registration', 10000, 10000, 10000, 10000],
        ['UV06', 'Operating Without Franchise (Colorum)', 50000, 100000, 200000, 200000],
        ['UV07', 'Operating Outside the Authorized Route', 5000, 10000, 50000, 50000],
        ['UV08', 'Overcharging of Fare', 5000, 10000, 15000, 15000],
        ['UV09', 'Refusal to Convey Passengers', 5000, 10000, 15000, 15000],
        ['UV10', 'Trip Cutting', 5000, 10000, 15000, 15000],
        ['UV11', 'Overloading of Passengers', 1500, 3000, 5000, 5000],
        ['UV12', 'Defective Parts or Accessories (Unroadworthy Vehicle)', 500, 1000, 1500, 1500],
        ['UV13', 'Failure to Display Fare Matrix or Franchise Details', 500, 1000, 1500, 2000],
        ['UV14', 'Illegal Loading and Unloading of Passengers', 500, 1000, 1500, 1500],
    ];

    public function run()
    {
        DB::transaction(function () {
            // delete(), not truncate(): the violation_logs foreign key forbids truncating.
            ViolationCode::query()->delete();

            foreach (self::CODES as [$code, $name, $first, $second, $third, $fourth]) {
                ViolationCode::create([
                    'code' => $code,
                    'violation_name' => $name,
                    'first_offense' => $first,
                    'second_offense' => $second,
                    'third_offense' => $third,
                    'fourth_offense' => $fourth,
                ]);
            }
        });
    }
}
