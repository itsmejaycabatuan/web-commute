<?php

namespace App\Console\Commands;

use App\Models\VehicleScanSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeVehicleScans extends Command
{
    protected $signature = 'vehicle-scans:purge';

    protected $description = 'Delete the photos of vehicle scan sessions that expired or were never used, and old session records';

    public function handle(): int
    {
        $photos = 0;

        // Anything past its expiry, whatever state it stopped in, loses its photos.
        VehicleScanSession::where('expires_at', '<', now())->whereNotNull('files')->get()->each(function (VehicleScanSession $s) use (&$photos) {
            $s->deleteFiles();
            $photos++;
        });

        // Folders left without a row (a crashed upload): the row's id is the folder name.
        $orphans = 0;
        $disk = Storage::disk(VehicleScanSession::DISK);
        foreach ($disk->directories('vehicle-scans') as $dir) {
            // Not a folder an upload is writing right now.
            if ($disk->lastModified($dir) > now()->subMinutes(30)->getTimestamp()) {
                continue;
            }

            if (! VehicleScanSession::whereKey((int) basename($dir))->whereNotNull('files')->exists()) {
                $disk->deleteDirectory($dir);
                $orphans++;
            }
        }

        $rows = VehicleScanSession::where('created_at', '<', now()->subDays(2))->delete();

        $this->info("Deleted photos of {$photos} session(s), {$orphans} orphan folder(s) and {$rows} old record(s).");

        return self::SUCCESS;
    }
}
