<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\FileLifecycleService;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class BulkChargebacksController extends Controller
{
    use LogsConditionally;

    public function __construct(
        protected FileLifecycleService $fileLifecycleService
    ) {
    }

    public function index(): View
    {
        return view('merchant.payments.bulk-chargebacks');
    }

    public function upload(Request $request): JsonResponse
    {
        $tempPath = null;

        try {
            $merchant = $request->user()->merchant;
            
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
            ]);

            $file = $request->file('file');
            $tempPath = $this->fileLifecycleService->storeTempUpload($file, 'bulk_chargebacks');

            // Process file and create job record (filtered by merchant)
            $job = DB::table('bulk_chargeback_jobs')->insertGetId([
                'job_name' => 'Bulk Chargeback Upload - ' . basename($tempPath),
                'file_path' => $tempPath,
                'status' => 'processing',
                'progress' => 0,
                'started_at' => now(),
                'user_id' => auth()->id(),
                'merchant_id' => $merchant->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully. Processing started.',
                'job_id' => $job,
            ]);
        } catch (\Exception $e) {
            if ($tempPath) {
                $this->fileLifecycleService->moveToFailedUploads($tempPath, $e->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getJobs(Request $request): JsonResponse
    {
        try {
            $merchant = $request->user()->merchant;
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = DB::table('bulk_chargeback_jobs')
                ->where('merchant_id', $merchant->id)
                ->latest();

            if ($request->has('filter_status') && $request->get('filter_status') !== 'all') {
                $query->where('status', $request->get('filter_status'));
            }

            $jobs = $query->paginate($perPage);

            $data = collect($jobs->items())->map(function($job) {
                return [
                    'id' => $job->id,
                    'job_id' => $job->id,
                    'job_name' => $job->job_name ?? '-',
                    'progress' => $job->progress ?? 0,
                    'status' => $job->status ?? 'pending',
                    'export_files' => $job->export_file_path ?? '-',
                    'started_at' => $job->started_at ? date('Y-m-d H:i:s', strtotime($job->started_at)) : '-',
                    'finished_at' => $job->finished_at ? date('Y-m-d H:i:s', strtotime($job->finished_at)) : '-',
                    'error' => $job->error ?? '-',
                    'user_name' => auth()->user()->name ?? 'Merchant',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $jobs->currentPage(),
                    'per_page' => $jobs->perPage(),
                    'total' => $jobs->total(),
                    'last_page' => $jobs->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch jobs',
            ], 500);
        }
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $relativePath = $this->fileLifecycleService->createCsvReport('bulk_chargeback_template.csv', function ($file): void {
            fputcsv($file, ['Chargeback Request ID', 'Merchant ID', 'Transaction ID', 'Amount', 'Status']);
        });

        return $this->fileLifecycleService->downloadAndDelete(
            $relativePath,
            'bulk_chargeback_template.csv',
            ['Content-Type' => 'text/csv']
        );
    }
}


