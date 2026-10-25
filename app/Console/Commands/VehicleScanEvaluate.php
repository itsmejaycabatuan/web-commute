<?php

namespace App\Console\Commands;

use App\Services\VehicleScan\FieldNormalizer;
use App\Services\VehicleScan\RegistrationDocumentReader;
use Illuminate\Console\Command;

/**
 * Developer tool: run the real reader over the synthetic OR/CR specimens (tests/Fixtures/ocr) and score what it
 * would put in the vehicle form against ground_truth.json. Reports, per field and per variant:
 *   right    the form would hold the correct value
 *   check    right or wrong, but the reader flagged it for a second look
 *   blank    not read; the manager types it
 *   WRONG    a wrong value marked "ok": the case the confirmation step must never have to catch alone
 */
class VehicleScanEvaluate extends Command
{
    protected $signature = 'vehicle-scan:evaluate
        {--samples=tests/Fixtures/ocr/samples : folder made by generate_samples.py}
        {--only= : comma list of variants (clean,scan,photo,photocopy) or file name parts}
        {--limit=0 : stop after this many files}
        {--detail : list every value that was not right}';

    protected $description = 'Score the registration-document reader against the synthetic specimens';

    private const FIELDS = ['plate_number', 'vin', 'brand', 'model', 'year', 'fuel_type'];

    public function handle(RegistrationDocumentReader $reader, FieldNormalizer $norm): int
    {
        $dir = base_path($this->option('samples'));
        $truthFile = $dir.'/ground_truth.json';

        if (! is_file($truthFile)) {
            $this->error('No specimens. Run: python3 tests/Fixtures/ocr/generate_samples.py');

            return self::FAILURE;
        }

        $truth = json_decode(file_get_contents($truthFile), true);

        if ($only = array_filter(explode(',', (string) $this->option('only')))) {
            $truth = array_values(array_filter($truth, fn ($e) => in_array($e['variant'], $only, true) || collect($only)->contains(fn ($o) => str_contains($e['file'], $o))));
        }

        if ((int) $this->option('limit') > 0) {
            $truth = array_slice($truth, 0, (int) $this->option('limit'));
        }

        $tally = [];
        $detail = [];
        $seconds = 0;

        foreach ($truth as $entry) {
            $started = microtime(true);
            $result = $reader->read([$dir.'/'.$entry['file']]);
            $seconds += microtime(true) - $started;

            foreach (self::FIELDS as $field) {
                if (! array_key_exists($field, $entry['expected'])) {
                    continue;
                }

                $want = $this->canonical($norm, $field, $entry['expected'][$field]);
                $got = $result['fields'][$field] ?? null;

                $verdict = ! $got ? 'blank' : ($this->canonical($norm, $field, $got['value']) === $want ? 'right' : 'wrong');
                $bucket = $verdict === 'wrong' && $got['status'] === 'check' ? 'check' : ($verdict === 'right' && $got['status'] === 'check' ? 'right' : $verdict);
                $tally[$entry['variant']][$field][$bucket === 'wrong' ? 'WRONG' : $bucket] = ($tally[$entry['variant']][$field][$bucket === 'wrong' ? 'WRONG' : $bucket] ?? 0) + 1;

                if ($verdict !== 'right') {
                    $detail[] = sprintf('  %-22s %-13s want %-20s got %-20s [%s]', $entry['file'], $field, $entry['expected'][$field], $got['value'] ?? '—', $got ? $got['status'] : 'blank');
                }
            }

            $this->output->write('.');
        }

        $this->newLine(2);
        $this->line('cells: right / check (wrong but flagged) / blank / WRONG (wrong and marked ok)');

        $variants = array_values(array_intersect(['clean', 'scan', 'photo', 'photocopy'], array_keys($tally)));
        $this->line(sprintf('%-14s', 'field').implode('', array_map(fn ($v) => sprintf('%-18s', $v), $variants)));

        foreach (self::FIELDS as $field) {
            $row = sprintf('%-14s', $field);
            foreach ($variants as $v) {
                $c = $tally[$v][$field] ?? null;
                $row .= sprintf('%-18s', $c ? (($c['right'] ?? 0).'/'.($c['check'] ?? 0).'/'.($c['blank'] ?? 0).'/'.($c['WRONG'] ?? 0)) : '-');
            }
            $this->line($row);
        }

        $this->newLine();
        foreach ($variants as $v) {
            $all = array_merge(...array_map(fn ($f) => array_map(null, [$tally[$v][$f]['right'] ?? 0], [$tally[$v][$f]['check'] ?? 0], [$tally[$v][$f]['blank'] ?? 0], [$tally[$v][$f]['WRONG'] ?? 0]), array_keys($tally[$v])));
            $sum = fn ($i) => array_sum(array_column($all, $i));
            $total = array_sum(array_map(fn ($i) => $sum($i), [0, 1, 2, 3]));
            $this->line(sprintf('%-10s correct in form %3d%%   flagged-wrong %d   blank %d   WRONG-and-trusted %d   (of %d values)',
                $v, $total ? round(100 * $sum(0) / $total) : 0, $sum(1), $sum(2), $sum(3), $total));
        }

        $this->line(sprintf("\n%.1f s per file", $truth ? $seconds / count($truth) : 0));

        if ($this->option('detail') && $detail) {
            $this->newLine();
            $this->line('Not right:');
            foreach ($detail as $line) {
                $this->line($line);
            }
        }

        return self::SUCCESS;
    }

    private function canonical(FieldNormalizer $norm, string $field, $value): string
    {
        return match ($field) {
            'plate_number' => preg_replace('/\W/', '', strtoupper((string) $value)),
            'vin' => strtoupper(preg_replace('/\W/', '', (string) $value)),
            'brand' => strtoupper(preg_replace('/\W/', '', (string) $value)),
            'model' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value)),
            'fuel_type' => (string) ($norm->fuel((string) $value)['value'] ?? $value),
            default => (string) $value,
        };
    }
}
