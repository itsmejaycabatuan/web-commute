<?php

namespace App\Services\VehicleScan;

use RuntimeException;

/**
 * Gets an uploaded photo ready to be read: upright (phones record the rotation in EXIF instead of turning
 * the pixels), a sensible size, flat colour, saved as a lossless PNG. The upload itself is never kept in this form
 * longer than the scan session.
 */
class ImagePreparer
{
    /** @return array{path: string, width: int, height: int} */
    public function prepare(string $source, string $destination): array
    {
        $bytes = @file_get_contents($source);

        if ($bytes === false || @getimagesizefromstring($bytes) === false) {
            throw new RuntimeException('That file is not a picture we can read.');
        }

        @ini_set('memory_limit', '768M');
        $image = @imagecreatefromstring($bytes);
        unset($bytes);

        if (! $image) {
            throw new RuntimeException('That file is not a picture we can read.');
        }

        $image = $this->upright($image, $source);
        $image = $this->flatten($image);
        $image = $this->fitSize($image);

        imagefilter($image, IMG_FILTER_GRAYSCALE);

        if (! imagepng($image, $destination, 3)) {
            throw new RuntimeException('The picture could not be prepared.');
        }

        $size = ['path' => $destination, 'width' => imagesx($image), 'height' => imagesy($image)];
        imagedestroy($image);

        return $size;
    }

    /** A turned copy of an already prepared page (for a document photographed sideways or upside down). */
    public function rotated(string $pngPath, int $degrees, string $destination): string
    {
        $image = imagecreatefrompng($pngPath);
        $turned = imagerotate($image, -$degrees, 0xFFFFFF);
        imagedestroy($image);

        imagepng($turned, $destination, 3);
        imagedestroy($turned);

        return $destination;
    }

    private function upright($image, string $source)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($source);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $degrees = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        if ($degrees === 0) {
            return $image;
        }

        $turned = imagerotate($image, $degrees, 0xFFFFFF);
        imagedestroy($image);

        return $turned;
    }

    /** Transparent PNGs and screenshots: put them on white so black text stays black. */
    private function flatten($image)
    {
        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($flat, 0, 0, 0xFFFFFF);
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);

        return $flat;
    }

    private function fitSize($image)
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $long = max($w, $h);
        $max = (int) config('vehicle_scan.max_side', 3200);
        $min = (int) config('vehicle_scan.min_side', 2400);

        $target = $long > $max ? $max : ($long < $min ? $min : $long);

        if ($target === $long) {
            return $image;
        }

        $scaled = imagescale($image, (int) round($w * $target / $long), (int) round($h * $target / $long), IMG_BICUBIC);
        imagedestroy($image);

        return $scaled;
    }
}
