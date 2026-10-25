<?php

namespace App\Services\VehicleScan;

use RuntimeException;

/**
 * Photos of an OR / CR in, vehicle form fields out.
 *
 * For each photo: prepare it, read it with Tesseract (two passes, merged), turn it upright if the labels are
 * not found, then read the cells next to the labels. The two documents are combined (the CR is preferred) and
 * every value comes back with how far to trust it:
 *
 *   ok     read with high confidence in the shape the field should have
 *   check  readable but uncertain, or a look-alike character had to be corrected: the manager looks twice
 *   (absent) could not be read: the form field is left empty
 *
 * Nothing here decides that a value is right. The manager confirms every value before it is saved.
 */
class RegistrationDocumentReader
{
    public function __construct(
        private ImagePreparer $images,
        private TesseractClient $tesseract,
        private DocumentExtractor $extractor,
        private FieldNormalizer $normalizer,
    ) {
    }

    /**
     * @param  array<int, string>  $paths  uploaded photo files
     * @return array{fields: array, documents: array, warnings: array}
     */
    public function read(array $paths): array
    {
        if (! $this->tesseract->available()) {
            throw new RuntimeException('The reader (Tesseract) is not installed on this server.');
        }

        $documents = [];
        $warnings = [];

        foreach (array_values($paths) as $n => $path) {
            $label = 'photo '.($n + 1);

            try {
                $doc = $this->readOne($path);
            } catch (RuntimeException $e) {
                $warnings[] = ucfirst($label).': '.$e->getMessage();
                continue;
            }

            if (! $doc['type']) {
                $warnings[] = ucfirst($label).' does not look like an LTO Official Receipt or Certificate of Registration, or it is too blurry to read. Take it again, flat and in good light.';
                continue;
            }

            $doc['label'] = $label;
            $documents[] = $doc;
        }

        return ['fields' => $this->combine($documents, $warnings), 'documents' => array_map(fn ($d) => ['type' => $d['type'], 'label' => $d['label']], $documents), 'warnings' => $warnings];
    }

    /**
     * One photo: its type and what each OCR pass read from it.
     *
     * @return array{type: ?string, passes: array<int, array>}  `passes` = per-pass cell values, then a merged read as the last entry
     */
    public function readOne(string $path): array
    {
        $work = tempnam(sys_get_temp_dir(), 'scan');
        $files = [$work];

        try {
            $upright = $this->images->prepare($path, $work.'.png')['path'];
            $files[] = $upright;

            // First pass: is this a document, and the right way up?
            $first = $this->tesseract->words($upright, 6, 2);
            $best = ['path' => $upright, 'words' => $first, 'score' => max($this->extractor->anchors($first))];

            if ($best['score'] < 3) {
                foreach ([90, 270, 180] as $degrees) {
                    $turned = $this->images->rotated($upright, $degrees, $work.'-'.$degrees.'.png');
                    $files[] = $turned;
                    $w = $this->tesseract->words($turned, 6, 2);
                    $score = max($this->extractor->anchors($w));

                    if ($score > $best['score']) {
                        $best = ['path' => $turned, 'words' => $w, 'score' => $score];
                    }

                    if ($score >= 4) {
                        break;
                    }
                }
            }

            if ($best['score'] < 3) {
                return ['type' => null, 'passes' => []];
            }

            // Independent reads of the same page: sparse text mode and a different thresholding. Where they agree a
            // value is trustworthy; where they differ the manager is shown the alternatives.
            $reads = [
                $best['words'],
                $this->tesseract->words($best['path'], 11, 2),
                $this->tesseract->words($best['path'], 6, 0),
            ];

            $type = $this->extractor->detectType($this->tesseract->merge(...$reads));

            if (! $type) {
                return ['type' => null, 'passes' => []];
            }

            $passes = array_map(fn ($words) => $this->extractor->extract($words, $type), $reads);
            // A merged read fills words one pass dropped (a tilted row); it is a fallback, never a vote.
            $passes[] = $this->extractor->extract($this->tesseract->merge(...$reads), $type);

            return ['type' => $type, 'passes' => $passes];
        } finally {
            foreach (array_unique($files) as $f) {
                @unlink($f);
            }
        }
    }

    /** Fields that come from a closed list or a strict shape: a low Tesseract score matters less than for free text. */
    private const CLOSED = ['plate_number', 'year', 'fuel_type', 'brand'];

    /** field => [normalizer method, cell label, always "check"] */
    private const MAP = [
        'plate_number' => ['plate', 'plate', false],
        'vin' => ['chassis', 'chassis', true],
        'brand' => ['make', 'make', false],
        'model' => ['series', 'series', false],
        'year' => ['year', 'year', false],
        'fuel_type' => ['fuel', 'fuel', false],
    ];

    /** Merge the documents' values into the form's fields. */
    private function combine(array $documents, array &$warnings): array
    {
        $perDocument = [];

        foreach ($documents as $doc) {
            foreach (self::MAP as $field => [$method, $cell, $always]) {
                $choice = $this->vote($doc['passes'], $cell, $method, $field, $always);

                if ($choice) {
                    $perDocument[$doc['type']][$field] = $choice + ['source' => $doc['type']];
                }
            }
        }

        $fields = [];

        foreach (array_keys(self::MAP) as $field) {
            $cr = $perDocument['CR'][$field] ?? null;
            $or = $perDocument['OR'][$field] ?? null;

            if ($cr && $or) {
                if ($this->same($field, $cr['value'], $or['value'])) {
                    // Two different papers, read independently, say the same thing.
                    $fields[$field] = array_merge($cr, ['status' => $cr['status'] === 'check' && $field === 'vin' ? 'check' : 'ok', 'source' => 'CR+OR']);
                } else {
                    $fields[$field] = array_merge($cr, ['status' => 'check', 'alternatives' => array_values(array_unique(array_merge($cr['alternatives'] ?? [], [$or['value']])))]);
                    $warnings[] = ucfirst(str_replace('_', ' ', $field))." differs between the papers: CR {$cr['value']}, OR {$or['value']}. Check which is right.";
                }
            } elseif ($cr || $or) {
                $fields[$field] = $cr ?: $or;
            }
        }

        return $fields;
    }

    /**
     * Pick a field's value from what each pass read.
     *
     * @return ?array{value: string, status: string, confidence: int, alternatives?: array}
     */
    private function vote(array $passes, string $cell, string $method, string $field, bool $alwaysCheck): ?array
    {
        $closed = in_array($field, self::CLOSED, true);
        $floor = $closed ? 15.0 : (float) config('vehicle_scan.min_confidence', 45);
        $groups = [];

        foreach ($passes as $i => $values) {
            if (! isset($values[$cell])) {
                continue;
            }

            $norm = $this->normalizer->{$method}($values[$cell]['raw']);
            $conf = (float) $values[$cell]['conf'];

            if ($norm['value'] === null || ! $norm['valid'] || $conf < $floor) {
                continue;
            }

            $g = &$groups[$norm['value']];
            $g['value'] = $norm['value'];
            $g['corrected'] = ($g['corrected'] ?? false) || $norm['corrected'];
            $g['conf'] = max($g['conf'] ?? 0, $conf);
            $g['votes'] = $g['votes'] ?? [];

            // The last entry is the merged read: it can supply a value but it is not an independent vote.
            if ($i < count($passes) - 1) {
                $g['votes'][$i] = true;
            }
            unset($g);
        }

        if (! $groups) {
            return null;
        }

        usort($groups, fn ($a, $b) => [count($b['votes']), $b['conf']] <=> [count($a['votes']), $a['conf']]);
        $best = $groups[0];
        $support = count($best['votes']);

        $status = 'check';
        if (! $alwaysCheck && ! $best['corrected'] && count($groups) === 1 && $support >= 2) {
            $status = 'ok';
        } elseif (! $alwaysCheck && ! $best['corrected'] && $support >= 2 && $support > count($groups[1]['votes'] ?? [])) {
            $status = 'ok';
        }

        $out = ['value' => $best['value'], 'status' => $status, 'confidence' => (int) round($best['conf'])];

        if (count($groups) > 1) {
            $out['alternatives'] = array_slice(array_column($groups, 'value'), 1, 3);
            $out['status'] = 'check';
        }

        return $out;
    }

    private function same(string $field, string $a, string $b): bool
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $a)) === strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $b));
    }
}
