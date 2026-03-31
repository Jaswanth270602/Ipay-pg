<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileLifecycleService
{
    protected string $disk = 'local';

    public function ensureBaseDirectories(): void
    {
        $storage = Storage::disk($this->disk);
        $storage->makeDirectory('temp');
        $storage->makeDirectory('reports');
        $storage->makeDirectory('failed_uploads');
    }

    public function storeTempUpload(UploadedFile $file, ?string $prefix = null): string
    {
        $this->ensureBaseDirectories();

        $safePrefix = $prefix ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $prefix) . '_' : '';
        $name = now()->format('Ymd_His') . '_' . $safePrefix . $file->getClientOriginalName();

        return $file->storeAs('temp', $name, $this->disk);
    }

    public function deleteIfExists(string $path): void
    {
        $storage = Storage::disk($this->disk);
        if ($storage->exists($path)) {
            $storage->delete($path);
        }
    }

    public function moveToFailedUploads(string $path, ?string $reason = null): ?string
    {
        $storage = Storage::disk($this->disk);
        if (! $storage->exists($path)) {
            return null;
        }

        $failedName = basename($path);
        $failedPath = 'failed_uploads/' . $failedName;
        $storage->move($path, $failedPath);

        Log::warning('File moved to failed_uploads', [
            'source' => $path,
            'target' => $failedPath,
            'reason' => $reason,
        ]);

        return $failedPath;
    }

    public function saveReportContents(string $fileName, string $contents): string
    {
        $this->ensureBaseDirectories();
        $relativePath = 'reports/' . $fileName;
        Storage::disk($this->disk)->put($relativePath, $contents);
        return $relativePath;
    }

    /**
     * Build a CSV report under storage/app/reports and return relative path.
     *
     * @param  callable  $writer  function(resource $handle): void
     */
    public function createCsvReport(string $fileName, callable $writer): string
    {
        $this->ensureBaseDirectories();

        $relativePath = 'reports/' . $fileName;
        $fullPath = Storage::disk($this->disk)->path($relativePath);

        $handle = fopen($fullPath, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Unable to create CSV file: ' . $fileName);
        }

        try {
            $writer($handle);
        } finally {
            fclose($handle);
        }

        return $relativePath;
    }

    /**
     * Download and delete file after response send.
     */
    public function downloadAndDelete(string $relativePath, ?string $downloadName = null, array $headers = []): BinaryFileResponse
    {
        $storage = Storage::disk($this->disk);
        $fullPath = $storage->path($relativePath);

        if (! $storage->exists($relativePath)) {
            abort(404, 'File not found');
        }

        return response()
            ->download($fullPath, $downloadName ?? basename($relativePath), $headers)
            ->deleteFileAfterSend(true);
    }
}

