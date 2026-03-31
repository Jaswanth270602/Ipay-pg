<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupCsvLifecycleFiles extends Command
{
    protected $signature = 'csv:cleanup-lifecycle-files';

    protected $description = 'Delete stale CSV temp and report files';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $deleted = 0;

        $deleted += $this->deleteOlderThan($disk, 'temp', now()->subHour()->getTimestamp(), 'temp');
        $deleted += $this->deleteOlderThan($disk, 'reports', now()->subDay()->getTimestamp(), 'reports');

        $this->info("CSV lifecycle cleanup complete. Deleted files: {$deleted}");
        return self::SUCCESS;
    }

    protected function deleteOlderThan($disk, string $dir, int $thresholdUnix, string $label): int
    {
        if (! $disk->exists($dir)) {
            return 0;
        }

        $count = 0;
        foreach ($disk->allFiles($dir) as $file) {
            try {
                $lastModified = $disk->lastModified($file);
                if ($lastModified <= $thresholdUnix) {
                    $disk->delete($file);
                    $count++;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to cleanup CSV lifecycle file', [
                    'file' => $file,
                    'bucket' => $label,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}

