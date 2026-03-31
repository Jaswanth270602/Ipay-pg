<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CsvProcessingService;
use App\Services\FileLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CsvLifecycleController extends Controller
{
    public function __construct(
        protected CsvProcessingService $csvProcessingService,
        protected FileLifecycleService $fileLifecycleService
    ) {
    }

    /**
     * Upload CSV -> store temporary -> process immediately -> cleanup/move on failure.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $tempPath = $this->fileLifecycleService->storeTempUpload($file, 'csv_lifecycle');
        $fullPath = Storage::disk('local')->path($tempPath);

        try {
            $result = $this->csvProcessingService->processRefundCsv(
                $fullPath,
                (int) auth()->id(),
                null // admin mode, no merchant filter
            );

            // Success: remove temp file
            $this->fileLifecycleService->deleteIfExists($tempPath);

            return response()->json([
                'success' => true,
                'message' => 'CSV processed successfully.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            // Failure: move file to failed_uploads for debugging
            $failedPath = $this->fileLifecycleService->moveToFailedUploads($tempPath, $e->getMessage());

            Log::error('CSV upload processing failed', [
                'temp_path' => $tempPath,
                'failed_path' => $failedPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'CSV processing failed. File moved to failed_uploads.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate report CSV in storage/app/reports and auto-delete after send.
     */
    public function downloadReport(Request $request)
    {
        $fileName = 'refund_report_' . now()->format('Ymd_His') . '.csv';
        $relativePath = 'reports/' . $fileName;
        $fullPath = Storage::disk('local')->path($relativePath);

        try {
            $handle = fopen($fullPath, 'w');
            if (!$handle) {
                throw new \RuntimeException("Unable to create report file at {$fullPath}");
            }

            fputcsv($handle, ['refund_id', 'transaction_id', 'merchant_id', 'amount', 'status', 'created_at']);

            $rows = \DB::table('refunds')
                ->select('refund_id', 'transaction_id', 'merchant_id', 'amount', 'status', 'created_at')
                ->orderByDesc('id')
                ->limit(5000)
                ->get();

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->refund_id,
                    $row->transaction_id,
                    $row->merchant_id,
                    $row->amount,
                    $row->status,
                    $row->created_at,
                ]);
            }

            fclose($handle);

            return $this->fileLifecycleService->downloadAndDelete(
                $relativePath,
                $fileName,
                ['Content-Type' => 'text/csv']
            );
        } catch (\Throwable $e) {
            Log::error('Failed to generate CSV report', [
                'report_path' => $relativePath,
                'error' => $e->getMessage(),
            ]);

            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            $this->fileLifecycleService->deleteIfExists($relativePath);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report CSV.',
            ], 500);
        }
    }
}

