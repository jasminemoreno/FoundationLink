<?php

namespace App\Console\Commands;

use App\Support\PrivateFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Moves IDs, foundation documents and payment receipts uploaded before they
 * became private out of the public folder. The paths saved in the database
 * stay the same. Safe to run again; it never overwrites a private file.
 *
 *   php artisan files:make-private
 */
class MakeFilesPrivate extends Command
{
    protected $signature = 'files:make-private';

    protected $description = 'Move IDs, foundation documents and payment receipts out of the public folder';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $moved = 0;
        $skipped = [];

        foreach (PrivateFile::FOLDERS as $folder) {
            foreach ($public->allFiles($folder) as $path) {
                if ($private->exists($path)) {
                    $skipped[] = $path;
                    continue;
                }

                $private->writeStream($path, $public->readStream($path));

                // delete the public copy only once the private one is complete
                if ($private->size($path) !== $public->size($path)) {
                    $private->delete($path);
                    $this->error("Could not copy {$path}; it was left where it is.");
                    return self::FAILURE;
                }

                $public->delete($path);
                $moved++;
            }
        }

        $this->info("Moved {$moved} file(s) to the private folder.");
        foreach ($skipped as $path) {
            $this->warn("Left in the public folder, a private file with this name already exists: {$path}");
        }

        return self::SUCCESS;
    }
}
