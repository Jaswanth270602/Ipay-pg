<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FileLifecycleService;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class BulkChargebacksUploadController extends Controller
{
    use LogsConditionally;

    public function __construct(
        protected FileLifecycleService $fileLifecycleService
    ) {
    }

    public function index(): View
    {
        $this->logInfo('Admin bulk chargebacks upload page accessed', ['user_id' => auth()->id()]);
        return view('admin.payments.bulk-chargebacks-upload');
    }

    public function upload(Request $request): JsonResponse
    {
        $tempPath = null;

        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
            ]);

            $file = $request->file('file');
            $tempPath = $this->fileLifecycleService->storeTempUpload($file, 'bulk_chargebacks_upload');

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
            ]);
        } catch (\Exception $e) {
            if ($tempPath) {
                $this->fileLifecycleService->moveToFailedUploads($tempPath, $e->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file',
            ], 500);
        }
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $relativePath = $this->fileLifecycleService->createCsvReport('bulk_chargeback_template.csv', function ($file): void {
            fputcsv($file, ['Transaction ID', 'Chargeback Amount', 'Reason']);
        });

        return $this->fileLifecycleService->downloadAndDelete(
            $relativePath,
            'bulk_chargeback_template.csv',
            ['Content-Type' => 'text/csv']
        );
    }
}

