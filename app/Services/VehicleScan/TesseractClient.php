<?php

namespace App\Services\VehicleScan;

use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Runs the local `tesseract` program and hands back the words it found with their boxes and confidence.
 * Nothing leaves the server.
 */
class TesseractClient
{
    private static ?bool $available = null;

    public function available(): bool
    {
        if (self::$available !== null) {
            return self::$available;
        }

        try {
            $process = new Process([config('vehicle_scan.tesseract_binary', 'tesseract'), '--version']);
            $process->setTimeout(10);
            $process->run();

            return self::$available = $process->isSuccessful();
        } catch (\Throwable) {
            return self::$available = false;
        }
    }

    /** Test seam. */
    public static function forgetAvailability(): void
    {
        self::$available = null;
    }

    /**
     * @param  int  $psm        page segmentation mode: 6 = one uniform block, 11 = sparse text, anywhere
     * @param  int  $threshold  Tesseract's thresholding method: 0 Otsu, 1 adaptive Otsu, 2 Sauvola
     * @return array<int, array{text: string, conf: float, left: int, top: int, width: int, height: int}>
     */
    public function words(string $imagePath, int $psm = 6, int $threshold = 2): array
    {
        $process = new Process([
            config('vehicle_scan.tesseract_binary', 'tesseract'), $imagePath, 'stdout',
            '--psm', (string) $psm, '-l', 'eng', '-c', 'thresholding_method='.$threshold, 'tsv',
        ], null, ['OMP_THREAD_LIMIT' => (string) max(1, (int) config('vehicle_scan.threads', 2))]);

        $process->setTimeout((int) config('vehicle_scan.pass_timeout', 60));

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw new RuntimeException('Reading the page took too long.');
        }

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Tesseract failed: '.trim($process->getErrorOutput()));
        }

        return $this->parse($process->getOutput());
    }

    /** Word rows (level 5) of Tesseract's TSV output. */
    public function parse(string $tsv): array
    {
        $words = [];

        foreach (preg_split('/\R/', $tsv) as $i => $line) {
            if ($i === 0 || $line === '') {
                continue;
            }

            $c = explode("\t", $line, 12);

            if (count($c) < 12 || (int) $c[0] !== 5 || trim($c[11]) === '' || (float) $c[10] < 0) {
                continue;
            }

            $words[] = [
                'text' => trim($c[11]), 'conf' => (float) $c[10],
                'left' => (int) $c[6], 'top' => (int) $c[7], 'width' => (int) $c[8], 'height' => (int) $c[9],
            ];
        }

        return $words;
    }

    /**
     * Combine passes: words from the first list win; a word from a later list is added only where no
     * accepted word already sits (its centre is outside every accepted box).
     */
    public function merge(array ...$passes): array
    {
        $merged = array_shift($passes) ?? [];

        foreach ($passes as $pass) {
            foreach ($pass as $word) {
                $cx = $word['left'] + $word['width'] / 2;
                $cy = $word['top'] + $word['height'] / 2;
                $covered = false;

                foreach ($merged as $m) {
                    if ($cx >= $m['left'] && $cx <= $m['left'] + $m['width'] && $cy >= $m['top'] && $cy <= $m['top'] + $m['height']) {
                        $covered = true;
                        break;
                    }
                }

                if (! $covered) {
                    $merged[] = $word;
                }
            }
        }

        return $merged;
    }
}
