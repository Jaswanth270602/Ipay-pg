<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessBulkRefundUpdateJob;

class BulkRefundUpdateController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        return view('merchant.payments.bulk-refund-update');
    }

    public function upload(Request $request): JsonResponse
    {
        try {
            $merchant = $request->user()->merchant;
            
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:10240',
            ]);

            $file = $request->file('file');
            if (!$this->hasValidRefundHeaders($file->getRealPath())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file format - column mismatch with refund form',
                ], 422);
            }

            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('bulk_refunds', $fileName, 'local');

            // Process file and create job record (filtered by merchant)
            $job = DB::table('bulk_refund_jobs')->insertGetId([
                'job_name' => 'Bulk Refund Update - ' . $fileName,
                'file_path' => $filePath,
                'status' => 'pending',
                'progress' => 0,
                'started_at' => null,
                'user_id' => auth()->id(),
                'merchant_id' => $merchant->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Queue job to process file
            ProcessBulkRefundUpdateJob::dispatch($job, $filePath);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully. Processing started.',
                'job_id' => $job,
            ]);
        } catch (\Exception $e) {
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
            
            $query = DB::table('bulk_refund_jobs')
                ->where('merchant_id', $merchant->id)
                ->latest();

            // Filters
            if ($request->has('filter_job_id') && $request->get('filter_job_id')) {
                $query->where('id', 'like', "%{$request->get('filter_job_id')}%");
            }
            if ($request->has('filter_job_name') && $request->get('filter_job_name')) {
                $query->where('job_name', 'like', "%{$request->get('filter_job_name')}%");
            }
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
                    'status_info' => $job->status_info ?? '-',
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

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bulk_refund_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            // Strict format aligned with merchant create refund form.
            fputcsv($file, ['transaction_id', 'amount', 'reason']);
            fputcsv($file, ['TXN_SAMPLE_001', '100.00', 'SAMPLE - Replace with valid transaction_id']);
            fputcsv($file, ['TXN_SAMPLE_002', '50.00', 'SAMPLE - Replace with valid transaction_id']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadStatusFile($id): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $merchant = auth()->user()->merchant;
            $job = DB::table('bulk_refund_jobs')
                ->where('id', $id)
                ->where('merchant_id', $merchant->id)
                ->first();

            if (!$job || !$job->export_file_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found',
                ], 404);
            }

            if (!Storage::disk('local')->exists($job->export_file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File does not exist',
                ], 404);
            }

            return Storage::disk('local')->download($job->export_file_path);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file',
            ], 500);
        }
    }

    private function hasValidRefundHeaders(string $path): bool
    {
        $h = @fopen($path, 'r');
        if (!$h) {
            return false;
        }

        [, $header] = $this->readHeaderRow($h);
        fclose($h);

        if (!is_array($header)) {
            return false;
        }

        $normalized = array_map(static function ($v) {
            $value = trim((string) $v);
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value); // strip UTF-8 BOM
            return strtolower($value);
        }, $header);

        return $normalized === ['transaction_id', 'amount', 'reason'];
    }

    /**
     * @param resource $h
     * @return array{0:string,1:array<int,string>|null}
     */
    private function readHeaderRow($h): array
    {
        $firstLine = fgets($h);
        if ($firstLine === false) {
            return [',', null];
        }

        $firstLine = trim($firstLine);
        $delimiter = ',';

        // Excel may prepend "sep=," or "sep=;" on line 1.
        if (stripos($firstLine, 'sep=') === 0) {
            $delimiter = substr($firstLine, 4, 1) ?: ',';
            $header = fgetcsv($h, 0, $delimiter);
            return [$delimiter, $header ?: null];
        }

        $commaCount = substr_count($firstLine, ',');
        $semiCount = substr_count($firstLine, ';');
        $delimiter = $semiCount > $commaCount ? ';' : ',';

        // Rewind and read actual header using detected delimiter.
        rewind($h);
        $header = fgetcsv($h, 0, $delimiter);
        return [$delimiter, $header ?: null];
    }
}


