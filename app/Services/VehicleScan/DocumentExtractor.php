<?php

namespace App\Services\VehicleScan;

/**
 * Reads an LTO Certificate of Registration (CR) or Official Receipt (OR) from the words Tesseract found.
 *
 * It does not search the text for patterns. It finds each printed LABEL ("PLATE NO.", "CHASSIS NO.", ...)
 * by its position and reads the value in that label's cell: below it, in the grid of a CR; beside it, on the
 * same line, in the "Payment details" panel of an OR. A photo that is slightly tilted is straightened first
 * (the angle is measured from the labels themselves), so rows that slope on the page still line up.
 */
class DocumentExtractor
{
    /** Printed labels of a CR, as tokens. Every label is listed (not only the ones we read) so it can bound a cell. */
    private const CR_LABELS = [
        'mv_file' => ['MV', 'FILE', 'NO'], 'plate' => ['PLATE', 'NO'], 'engine' => ['ENGINE', 'NO'], 'chassis' => ['CHASSIS', 'NO'],
        'denomination' => ['DENOMINATION'], 'piston' => ['PISTON', 'DISPLACEMENT'], 'cylinders' => ['NO', 'OF', 'CYLINDER'], 'fuel' => ['FUEL'],
        'make' => ['MAKE'], 'series' => ['SERIES'], 'body_type' => ['BODY', 'TYPE'], 'body_no' => ['BODY', 'NO'], 'year' => ['YEAR', 'MODEL'],
        'gross' => ['GROSS', 'WT'], 'net_wt' => ['NET', 'WT'], 'shipping' => ['SHIPPING', 'WT'], 'capacity' => ['NET', 'CAPACITY'],
        'owner' => ['COMPLETE', 'OWNERS', 'NAME'], 'telephone' => ['TELEPHONE'], 'address' => ['COMPLETE', 'ADDRESS'],
        'encumbered' => ['ENCUMBERED', 'TO'], 'first_reg' => ['DETAILS', 'OF', 'FIRST', 'REGISTRATION'],
        'or_no' => ['OR', 'NO'], 'or_date' => ['OR', 'DATE'],
    ];

    /** Printed labels of an OR. */
    private const OR_LABELS = [
        'transaction' => ['TRANSACTION', 'NO'], 'plate' => ['PLATE', 'NO'], 'file_no' => ['FILE', 'NO'], 'classification' => ['CLASSIFICATION'],
        'vehicle_type' => ['VEHICLE', 'TYPE'], 'fuel' => ['FUEL', 'TYPE'], 'aircon' => ['AIRCON', 'TYPE'], 'color' => ['COLOR'],
        'year' => ['YEAR', 'MODEL'], 'breakdown' => ['BREAKDOWN', 'OF', 'PAYMENT'],
    ];

    /** Where each CR label sits in the form's grid (columns 1-4 align on every row; YEAR MODEL starts inside column 4). */
    private const CR_COLUMN = [
        'mv_file' => 1, 'denomination' => 1, 'make' => 1, 'gross' => 1,
        'plate' => 2, 'piston' => 2, 'series' => 2, 'net_wt' => 2,
        'engine' => 3, 'cylinders' => 3, 'body_type' => 3, 'shipping' => 3,
        'chassis' => 4, 'fuel' => 4, 'body_no' => 4, 'capacity' => 4,
    ];

    /** The grid row of each CR label. */
    private const CR_ROW = [
        'mv_file' => 1, 'plate' => 1, 'engine' => 1, 'chassis' => 1,
        'denomination' => 2, 'piston' => 2, 'cylinders' => 2, 'fuel' => 2,
        'make' => 3, 'series' => 3, 'body_type' => 3, 'body_no' => 3, 'year' => 3,
        'gross' => 4, 'net_wt' => 4, 'shipping' => 4, 'capacity' => 4,
        'owner' => 5, 'telephone' => 5, 'address' => 6, 'encumbered' => 7,
        'first_reg' => 8, 'or_no' => 8, 'or_date' => 8,
    ];

    /** Labels that prove which document this is. (PLATE NO. and YEAR MODEL are on both, so they prove nothing.) */
    private const CR_ANCHORS = ['engine', 'chassis', 'mv_file', 'make', 'series', 'body_type'];
    private const OR_ANCHORS = ['transaction', 'classification', 'aircon', 'fuel', 'vehicle_type', 'color'];

    /** The fields read from each document: field => label key. */
    private const CR_FIELDS = ['plate' => 'plate', 'engine' => 'engine', 'chassis' => 'chassis', 'mv_file' => 'mv_file', 'make' => 'make',
        'series' => 'series', 'year' => 'year', 'fuel' => 'fuel', 'body_type' => 'body_type'];
    private const OR_FIELDS = ['plate' => 'plate', 'fuel' => 'fuel', 'year' => 'year', 'color' => 'color', 'vehicle_type' => 'vehicle_type'];

    /**
     * How many of each document's anchor labels are on the page.
     *
     * @return array{CR: int, OR: int}
     */
    public function anchors(array $words): array
    {
        $words = $this->prepare($words);

        return [
            'CR' => count(array_intersect(array_keys($this->findLabels($words, self::CR_LABELS)), self::CR_ANCHORS)),
            'OR' => count(array_intersect(array_keys($this->findLabels($words, self::OR_LABELS)), self::OR_ANCHORS)),
        ];
    }

    /** 'CR', 'OR' or null when the page is neither (fewer than three anchor labels found). */
    public function detectType(array $words): ?string
    {
        $a = $this->anchors($words);

        if (max($a) < 3) {
            return null;
        }

        return $a['CR'] >= $a['OR'] ? 'CR' : 'OR';
    }

    /**
     * @return array<string, array{raw: string, conf: float}>  field => what was read and how sure Tesseract was (0-100)
     */
    public function extract(array $words, string $type): array
    {
        $words = $this->prepare($words);
        $defs = $type === 'CR' ? self::CR_LABELS : self::OR_LABELS;
        $labels = $this->findLabels($words, $defs);

        if (! $labels) {
            return [];
        }

        // Straighten: measure the tilt from the labels, then work in a de-rotated frame.
        $words = $this->derotate($words, $labels, $type === 'CR' ? $this->tilt($labels) : 0.0);
        $labels = $this->findLabels($words, $defs, true);

        if ($type === 'CR') {
            $labels = $this->completeGrid($labels, $defs);
        }

        $labelWords = [];
        foreach ($labels as $l) {
            foreach ($l['idx'] as $i) {
                $labelWords[$i] = true;
            }
        }

        $out = [];
        foreach (($type === 'CR' ? self::CR_FIELDS : self::OR_FIELDS) as $field => $key) {
            if (! isset($labels[$key])) {
                continue;
            }

            $picked = $type === 'CR'
                ? $this->cellBelow($labels[$key], $labels, $words, $labelWords)
                : $this->sameLine($labels[$key], $labels, $words, $labelWords);

            if ($picked) {
                $out[$field] = $picked;
            }
        }

        return $out;
    }

    // ── labels ───────────────────────────────────────────────────────────────────────────────────

    private function prepare(array $words): array
    {
        return array_values(array_map(function ($w) {
            $w['cx'] = $w['left'] + $w['width'] / 2;
            $w['cy'] = $w['top'] + $w['height'] / 2;
            $w['rx'] = $w['cx'];
            $w['ry'] = $w['cy'];

            return $w;
        }, array_filter($words, fn ($w) => $w['width'] > 0 && $w['height'] > 0)));
    }

    /** Letters and digits only, with look-alike digits read as letters: "N0." -> "NO". */
    private function tok(string $s): string
    {
        return strtr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $s)), ['0' => 'O', '1' => 'I', '5' => 'S', '8' => 'B']);
    }

    private function same(string $word, string $token): bool
    {
        $w = $this->tok($word);

        return $w === $token || (strlen($token) >= 4 && $w !== '' && levenshtein($w, $token) <= 1);
    }

    /**
     * Find labels on the page. `$rotated` uses the straightened coordinates (rx, ry).
     *
     * @return array<string, array>  label key => its box, centre and the words it is made of (best match per key)
     */
    private function findLabels(array $words, array $defs, bool $rotated = false): array
    {
        $x = $rotated ? 'rx' : 'cx';
        $y = $rotated ? 'ry' : 'cy';
        $found = [];

        foreach ($defs as $key => $tokens) {
            foreach ($words as $i => $first) {
                if (! $this->same($first['text'], $tokens[0])) {
                    continue;
                }

                $chain = [$i];
                $last = $first;

                for ($t = 1; $t < count($tokens); $t++) {
                    $next = null;

                    foreach ($words as $j => $cand) {
                        if (in_array($j, $chain, true) || ! $this->same($cand['text'], $tokens[$t])) {
                            continue;
                        }

                        $h = max($last['height'], $cand['height']);
                        $gap = $cand['left'] - ($last['left'] + $last['width']);

                        if ($cand[$x] > $last[$x] && abs($cand[$y] - $last[$y]) <= 0.7 * $h && $gap >= -0.5 * $h && $gap <= 3 * $h
                            && ($next === null || $cand['left'] < $words[$next]['left'])) {
                            $next = $j;
                        }
                    }

                    if ($next === null) {
                        continue 2;
                    }

                    $chain[] = $next;
                    $last = $words[$next];
                }

                $ws = array_map(fn ($k) => $words[$k], $chain);
                $conf = array_sum(array_column($ws, 'conf')) / count($ws);

                if (! isset($found[$key]) || $conf > $found[$key]['conf']) {
                    $left = min(array_column($ws, 'left'));
                    $right = max(array_map(fn ($w) => $w['left'] + $w['width'], $ws));
                    $top = min(array_column($ws, 'top'));
                    $bottom = max(array_map(fn ($w) => $w['top'] + $w['height'], $ws));

                    // The same box in the straightened frame (identical to the page frame until derotate()).
                    $rl = min(array_map(fn ($w) => $w['rx'] - $w['width'] / 2, $ws));
                    $rr = max(array_map(fn ($w) => $w['rx'] + $w['width'] / 2, $ws));
                    $rt = min(array_map(fn ($w) => $w['ry'] - $w['height'] / 2, $ws));
                    $rb = max(array_map(fn ($w) => $w['ry'] + $w['height'] / 2, $ws));

                    $found[$key] = [
                        'key' => $key, 'idx' => $chain, 'conf' => $conf, 'h' => ($bottom - $top),
                        'l' => $left, 'r' => $right, 't' => $top, 'b' => $bottom,
                        'cx' => ($left + $right) / 2, 'cy' => ($top + $bottom) / 2,
                        'rl' => $rl, 'rr' => $rr, 'rt' => $rt, 'rb' => $rb, 'rx' => ($rl + $rr) / 2, 'ry' => ($rt + $rb) / 2,
                    ];
                }
            }
        }

        return $found;
    }

    /** Page tilt in radians: the median slope along the rows of a CR's grid. */
    private function tilt(array $labels): float
    {
        $rows = [['mv_file', 'plate', 'engine', 'chassis'], ['denomination', 'piston', 'cylinders', 'fuel'], ['make', 'series', 'body_type', 'year']];
        $angles = [];

        foreach ($rows as $row) {
            $in = array_values(array_filter(array_map(fn ($k) => $labels[$k] ?? null, $row)));
            usort($in, fn ($a, $b) => $a['cx'] <=> $b['cx']);

            if (count($in) >= 2) {
                $a = $in[0];
                $b = $in[count($in) - 1];

                if ($b['cx'] - $a['cx'] > 6 * $a['h']) {
                    $angles[] = atan2($b['cy'] - $a['cy'], $b['cx'] - $a['cx']);
                }
            }
        }

        if (! $angles) {
            return 0.0;
        }

        sort($angles);
        $theta = $angles[intdiv(count($angles), 2)];

        return abs($theta) > deg2rad(12) ? 0.0 : $theta;
    }

    private function derotate(array $words, array $labels, float $theta): array
    {
        $cos = cos($theta);
        $sin = sin($theta);

        foreach ($words as &$w) {
            $w['rx'] = $w['cx'] * $cos + $w['cy'] * $sin;
            $w['ry'] = -$w['cx'] * $sin + $w['cy'] * $cos;
        }

        return $words;
    }

    /**
     * A CR is a fixed grid. A label Tesseract dropped (small print, a smudge) can be put back where the grid says it
     * is: its column's left edge comes from the other labels in that column, its row's height from the others in that row.
     */
    private function completeGrid(array $labels, array $defs): array
    {
        foreach (array_keys($defs) as $key) {
            if (isset($labels[$key]) || ! isset(self::CR_ROW[$key], self::CR_COLUMN[$key])) {
                continue;
            }

            $sameRow = array_filter($labels, fn ($l) => (self::CR_ROW[$l['key']] ?? null) === self::CR_ROW[$key] && empty($l['virtual']));
            $sameCol = array_filter($labels, fn ($l) => (self::CR_COLUMN[$l['key']] ?? null) === self::CR_COLUMN[$key] && empty($l['virtual']));

            if (! $sameRow || ! $sameCol) {
                continue;
            }

            $h = $this->median(array_column($labels, 'h'));
            $rl = $this->median(array_column($sameCol, 'rl'));
            $rt = $this->median(array_column($sameRow, 'rt'));
            $width = 0.62 * $h * strlen(implode(' ', $defs[$key]));

            $labels[$key] = [
                'key' => $key, 'idx' => [], 'conf' => 0.0, 'h' => $h, 'virtual' => true,
                'l' => $rl, 'r' => $rl + $width, 't' => $rt, 'b' => $rt + $h, 'cx' => $rl + $width / 2, 'cy' => $rt + $h / 2,
                'rl' => $rl, 'rr' => $rl + $width, 'rt' => $rt, 'rb' => $rt + $h, 'rx' => $rl + $width / 2, 'ry' => $rt + $h / 2,
            ];
        }

        return $labels;
    }

    // ── values ───────────────────────────────────────────────────────────────────────────────────

    /** CR: the value sits in the cell, under the label (right-aligned, or left for the owner and address). */
    private function cellBelow(array $L, array $labels, array $words, array $labelWords): ?array
    {
        $h = $L['h'];

        $right = INF;
        $below = INF;

        foreach ($labels as $M) {
            if ($M['key'] === $L['key']) {
                continue;
            }

            if (abs($M['ry'] - $L['ry']) <= 2.2 * $h && $M['rl'] > $L['rl'] + $h) {
                $right = min($right, $M['rl'] - 0.2 * $h);
            }
        }

        // The label to the right on this row was not read (a tilted or smudged photo): the next column's labels on
        // the other rows give the same cell edge, because the grid's columns line up.
        $column = self::CR_COLUMN[$L['key']] ?? null;
        if (! is_finite($right) && $column !== null && $column < 4) {
            foreach ($labels as $M) {
                if ((self::CR_COLUMN[$M['key']] ?? null) === $column + 1) {
                    $right = min($right, $M['rl'] - 0.2 * $h);
                }
            }
        }

        foreach ($labels as $M) {
            if ($M['key'] !== $L['key'] && $M['ry'] > $L['ry'] + 2.2 * $h && $M['rr'] > $L['rl'] && $M['rl'] < $right) {
                $below = min($below, $M['rt']);
            }
        }

        // The label straight below was not read either: any label on the next grid row marks the same line.
        $row = self::CR_ROW[$L['key']] ?? null;
        if (! is_finite($below) && $row !== null) {
            $next = array_filter($labels, fn ($m) => (self::CR_ROW[$m['key']] ?? null) === $row + 1 && empty($m['virtual']));
            if ($next) {
                $below = $this->median(array_column($next, 'rt'));
            }
        }

        $below = is_finite($below) ? $below : $L['rb'] + 5 * $h;

        $candidates = [];
        foreach ($words as $i => $w) {
            if (isset($labelWords[$i]) || $w['conf'] < 20 || ! preg_match('/[A-Za-z0-9]/', $w['text'])) {
                continue;
            }

            // Specks and stamp fragments are far smaller than the printed value.
            if ($w['height'] < 0.55 * $h) {
                continue;
            }

            if ($w['rx'] >= $L['rl'] - 0.8 * $h && $w['rx'] <= $right && $w['ry'] > $L['ry'] + 0.35 * $h && $w['ry'] < $below) {
                $candidates[] = $w;
            }
        }

        // Values are printed larger than the labels: when some words are, small words beside them are label text
        // from the next cell, not part of the value.
        if ($candidates) {
            $tallest = max(array_column($candidates, 'height'));
            if ($tallest >= 1.25 * $h) {
                $candidates = array_values(array_filter($candidates, fn ($w) => $w['height'] >= 0.7 * $tallest));
            }
        }

        return $this->bestLine($candidates);
    }

    /** OR: the value follows the label on the same line, inside the "Payment details" panel. */
    private function sameLine(array $L, array $labels, array $words, array $labelWords): ?array
    {
        $limit = isset($labels['breakdown']) ? $labels['breakdown']['l'] - 0.5 * $labels['breakdown']['h'] : INF;
        $candidates = [];

        foreach ($words as $i => $w) {
            if (isset($labelWords[$i]) || $w['conf'] < 20 || ! preg_match('/[A-Za-z0-9]/', $w['text'])) {
                continue;
            }

            if ($w['left'] >= $L['r'] - 0.3 * $L['h'] && $w['left'] + $w['width'] <= $limit && abs($w['cy'] - $L['cy']) <= 0.85 * max($L['h'], $w['height'])) {
                $candidates[] = $w;
            }
        }

        // Words after a wide gap belong to another column.
        usort($candidates, fn ($a, $b) => $a['left'] <=> $b['left']);
        $kept = [];
        foreach ($candidates as $w) {
            if ($kept && $w['left'] - ($kept[count($kept) - 1]['left'] + $kept[count($kept) - 1]['width']) > 5 * $L['h']) {
                break;
            }
            $kept[] = $w;
        }

        return $this->bestLine($kept, $L['cy']);
    }

    /**
     * Group words into lines and return one: the line nearest `$nearY` when given (a value that follows its label on
     * the same line), otherwise the line with the most confident text.
     */
    private function bestLine(array $words, ?float $nearY = null): ?array
    {
        if (! $words) {
            return null;
        }

        usort($words, fn ($a, $b) => $a['ry'] <=> $b['ry']);
        $tolerance = 0.6 * $this->median(array_column($words, 'height'));

        $lines = [];
        foreach ($words as $w) {
            $placed = false;
            foreach ($lines as &$line) {
                if (abs($w['ry'] - $line['ry']) <= $tolerance) {
                    $line['words'][] = $w;
                    $line['ry'] = array_sum(array_column($line['words'], 'ry')) / count($line['words']);
                    $placed = true;
                    break;
                }
            }
            unset($line);

            if (! $placed) {
                $lines[] = ['ry' => $w['ry'], 'words' => [$w]];
            }
        }

        $best = null;
        $bestScore = -INF;

        foreach ($lines as $line) {
            $score = $nearY !== null
                ? -abs($line['ry'] - $nearY)
                : array_sum(array_map(fn ($w) => $w['conf'] * strlen($w['text']), $line['words']));

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $line['words'];
            }
        }

        usort($best, fn ($a, $b) => $a['rx'] <=> $b['rx']);
        $chars = array_sum(array_map(fn ($w) => strlen($w['text']), $best));

        return [
            'raw' => trim(implode(' ', array_column($best, 'text'))),
            'conf' => $chars ? array_sum(array_map(fn ($w) => $w['conf'] * strlen($w['text']), $best)) / $chars : 0.0,
        ];
    }

    private function median(array $values): float
    {
        sort($values);

        return (float) $values[intdiv(count($values), 2)];
    }
}
