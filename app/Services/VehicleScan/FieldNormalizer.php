<?php

namespace App\Services\VehicleScan;

/**
 * Turns what the OCR read into what the vehicle form holds, and says how far to trust it. Every method
 * returns ['value' => ?string, 'corrected' => bool, 'valid' => bool]: `corrected` means a look-alike
 * character was swapped (O for 0 and so on), `valid` means the result has the shape the field should have.
 */
class FieldNormalizer
{
    /** Makes sold in the Philippines, in the spelling the form uses. Anything else is kept as read. */
    public const MAKES = [
        'Toyota', 'Isuzu', 'Mitsubishi', 'Suzuki', 'Honda', 'Hyundai', 'Nissan', 'Ford', 'Mazda', 'Kia', 'Chevrolet',
        'Kymco', 'Yamaha', 'Kawasaki', 'Daihatsu', 'Hino', 'Fuso', 'Foton', 'JAC', 'Changan', 'MG', 'Subaru', 'Mahindra',
        'Tata', 'SYM', 'Rusi', 'Skygo', 'Bajaj', 'TVS', 'Motorstar', 'Hyosung', 'Piaggio', 'Vespa', 'Peugeot', 'Chery',
        'Geely', 'Dongfeng', 'Sinotruk', 'Volkswagen', 'BMW', 'Mercedes-Benz', 'Mercedes', 'UD', 'Tamaraw', 'Owner',
    ];

    private const DIGIT_FOR_LETTER = ['O' => '0', 'Q' => '0', 'D' => '0', 'I' => '1', 'L' => '1', 'S' => '5', 'B' => '8', 'Z' => '2', 'G' => '6'];
    private const LETTER_FOR_DIGIT = ['0' => 'O', '1' => 'I', '5' => 'S', '8' => 'B', '2' => 'Z', '6' => 'G'];

    /** Plate shapes: L = letter, D = digit. Cars, then motorcycles (old and new). */
    private const PLATE_SHAPES = ['LLLDDDD', 'LLLDDD', 'LDDDLL', 'LLDDDD', 'DDDLLL', 'LLDDD', 'LDDDDD'];

    public function plate(?string $raw): array
    {
        $s = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $raw));

        if (strlen($s) < 5 || strlen($s) > 8) {
            return $this->result($s ?: null, false, false);
        }

        $best = null;

        foreach (self::PLATE_SHAPES as $shape) {
            if (strlen($shape) !== strlen($s)) {
                continue;
            }

            $fixed = '';
            $changes = 0;
            $ok = true;

            foreach (str_split($shape) as $i => $kind) {
                $c = $s[$i];
                $isDigit = ctype_digit($c);

                if ($kind === 'D' && ! $isDigit) {
                    if (! isset(self::DIGIT_FOR_LETTER[$c])) { $ok = false; break; }
                    $c = self::DIGIT_FOR_LETTER[$c];
                    $changes++;
                } elseif ($kind === 'L' && $isDigit) {
                    if (! isset(self::LETTER_FOR_DIGIT[$c])) { $ok = false; break; }
                    $c = self::LETTER_FOR_DIGIT[$c];
                    $changes++;
                }

                $fixed .= $c;
            }

            // Misread look-alikes are one or two characters at most; more than that is a different word, not a plate.
            if ($ok && $changes <= (strlen($shape) <= 5 ? 1 : 2) && ($best === null || $changes < $best['changes'])) {
                $best = ['value' => $fixed, 'changes' => $changes];
            }
        }

        if (! $best) {
            return $this->result($s, false, false);
        }

        // Three letters then digits is how a car plate is written: "ABC 1234".
        $value = preg_match('/^([A-Z]{3})(\d{3,4})$/', $best['value'], $m) ? "{$m[1]} {$m[2]}" : $best['value'];

        return $this->result($value, $best['changes'] > 0, true);
    }

    /** A chassis / VIN: 17 characters never contain I, O or Q, so those can be repaired safely. */
    public function chassis(?string $raw): array
    {
        $s = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $raw));

        if ($s === '') {
            return $this->result(null, false, false);
        }

        if (strlen($s) === 17) {
            $fixed = strtr($s, ['I' => '1', 'O' => '0', 'Q' => '0']);

            return $this->result($fixed, $fixed !== $s, true);
        }

        // Older and local chassis numbers are not 17 characters; accept a plausible length, but flag it.
        return $this->result($s, false, strlen($s) >= 6 && strlen($s) <= 25 && ! $this->flagged($s));
    }

    public function engine(?string $raw): array
    {
        $s = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $raw));

        return $this->result($s ?: null, false, strlen($s) >= 5 && strlen($s) <= 20);
    }

    public function mvFile(?string $raw): array
    {
        $s = strtoupper(preg_replace('/[^0-9\-]/', '', strtr((string) $raw, ['O' => '0', 'o' => '0', 'I' => '1', 'l' => '1'])));

        return $this->result($s ?: null, false, (bool) preg_match('/^\d{4}-\d{6,14}$/', $s));
    }

    public function year(?string $raw): array
    {
        $digits = preg_replace('/[^0-9]/', '', strtr(strtoupper((string) $raw), ['O' => '0', 'I' => '1', 'L' => '1', 'S' => '5', 'B' => '8']));

        if (strlen($digits) !== 4) {
            return $this->result(null, false, false);
        }

        $year = (int) $digits;
        $valid = $year >= 1950 && $year <= (int) date('Y') + 1;

        return $this->result($valid ? (string) $year : null, strtoupper(trim((string) $raw)) !== $digits, $valid);
    }

    /** The form's fuel options: Diesel, Gasoline, Electric, Hybrid, LPG. LTO writes "Gas" for gasoline. */
    public function fuel(?string $raw): array
    {
        $s = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $raw));

        $map = ['DIESEL' => 'Diesel', 'GAS' => 'Gasoline', 'GASOLINE' => 'Gasoline', 'PETROL' => 'Gasoline', 'ELECTRIC' => 'Electric',
                'HYBRID' => 'Hybrid', 'LPG' => 'LPG'];

        if (isset($map[$s])) {
            return $this->result($map[$s], false, true);
        }

        foreach ($map as $word => $value) {
            if (strlen($word) >= 5 && $s !== '' && levenshtein($s, $word) <= 1) {
                return $this->result($value, true, true);
            }
        }

        return $this->result(null, false, false);
    }

    public function make(?string $raw): array
    {
        $s = trim(preg_replace('/[^A-Za-z0-9 \-]/', '', (string) $raw));

        if ($s === '') {
            return $this->result(null, false, false);
        }

        $key = strtoupper(str_replace([' ', '-'], '', $s));

        foreach (self::MAKES as $make) {
            $candidate = strtoupper(str_replace([' ', '-'], '', $make));

            if ($key === $candidate) {
                return $this->result($make, false, true);
            }
            if (strlen($candidate) >= 4 && levenshtein($key, $candidate) <= (strlen($candidate) >= 7 ? 2 : 1)) {
                return $this->result($make, true, true);
            }
        }

        // An unfamiliar make: keep it as read, but it is only plausible if it looks like a word.
        return $this->result(ucwords(strtolower($s)), false, (bool) preg_match('/^[A-Za-z][A-Za-z0-9 \-]{1,24}$/', $s));
    }

    /** The series / model: letters, digits and the odd space or hyphen, as printed. */
    public function series(?string $raw): array
    {
        $s = strtoupper(trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9 \-]/', '', (string) $raw))));

        return $this->result($s ?: null, false, strlen($s) >= 2 && strlen($s) <= 30);
    }

    private function flagged(string $s): bool
    {
        return false;
    }

    private function result(?string $value, bool $corrected, bool $valid): array
    {
        return ['value' => $value, 'corrected' => $corrected, 'valid' => $valid];
    }
}
