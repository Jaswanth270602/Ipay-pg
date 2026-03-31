<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\FileLifecycleService;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessBulkRefundUpdateJob;

class BulkRefundUpdateController extends Controller
{
    use LogsConditionally;

    public function __construct(
        protected FileLifecycleService $fileLifecycleService
    ) {
    }

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

            $filePath = $this->fileLifecycleService->storeTempUpload($file, 'bulk_refunds');

            // Process file and create job record (filtered by merchant)
            $job = DB::table('bulk_refund_jobs')->insertGetId([
                'job_name' => 'Bulk Refund Update - ' . basename($filePath),
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
                    'export_files' => in_array($job->status ?? '', ['completed', 'completed_with_errors', 'failed'], true) ? 'available' : '-',
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

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $relativePath = $this->fileLifecycleService->createCsvReport('bulk_refund_template.csv', function ($file): void {
            // Strict format aligned with merchant create refund form.
            fputcsv($file, ['transaction_id', 'amount', 'reason']);
            fputcsv($file, ['TXN_SAMPLE_001', '100.00', 'SAMPLE - Replace with valid transaction_id']);
            fputcsv($file, ['TXN_SAMPLE_002', '50.00', 'SAMPLE - Replace with valid transaction_id']);
        });

        return $this->fileLifecycleService->downloadAndDelete(
            $relativePath,
            'bulk_refund_template.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    public function downloadStatusFile($id): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $merchant = auth()->user()->merchant;
            $job = DB::table('bulk_refund_jobs')
                ->where('id', $id)
                ->where('merchant_id', $merchant->id)
                ->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found',
                ], 404);
            }

            $user = DB::table('users')->where('id', $job->user_id ?? null)->first();
            $rowResults = $this->decodeRowResults($job->row_results_json ?? null);
            $fileName = 'bulk_refund_job_' . $id . '_status.xls';
            $relativePath = $this->fileLifecycleService->createCsvReport($fileName, function ($file) use ($job, $user, $rowResults): void {
                $formatDate = static function ($value): string {
                    if (empty($value)) {
                        return 'N/A';
                    }
                    return date('d-m-Y H:i:s', strtotime((string) $value));
                };
                $formatStatus = static function ($value): string {
                    $status = strtoupper((string) $value);
                    return match ($status) {
                        'SUCCESS' => 'SUCCESS (OK)',
                        'FAILED' => 'FAILED (ERROR)',
                        'COMPLETED' => 'COMPLETED (DONE)',
                        'COMPLETED_WITH_ERRORS' => 'COMPLETED WITH ERRORS (PARTIAL)',
                        'PROCESSING' => 'PROCESSING (IN PROGRESS)',
                        'PENDING' => 'PENDING (QUEUED)',
                        default => $status === '' ? 'N/A' : str_replace('_', ' ', $status),
                    };
                };

                $esc = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
                $status = strtoupper((string) ($job->status ?? 'N/A'));
                $statusBg = in_array($status, ['COMPLETED', 'SUCCESS'], true) ? '#e8f5e9' : ($status === 'FAILED' ? '#ffebee' : '#fff8e1');
                $statusFg = in_array($status, ['COMPLETED', 'SUCCESS'], true) ? '#1b5e20' : ($status === 'FAILED' ? '#b71c1c' : '#8a6d1d');

                $html = '<html><head><meta charset="UTF-8"></head><body>'
                    . '<table border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-family:Calibri,Arial,sans-serif;font-size:12px;">'
                    . '<tr><th colspan="4" style="background:#0d6efd;color:#fff;font-size:14px;text-align:left;">BULK REFUND STATUS REPORT</th></tr>'
                    . '<tr><td><b>Generated At</b></td><td colspan="3">' . $esc(now()->format('d-m-Y H:i:s')) . '</td></tr>'
                    . '<tr><td colspan="4" style="background:#f3f4f6;"><b>JOB DETAILS</b></td></tr>'
                    . '<tr><td><b>Job ID</b></td><td>' . $esc($job->id ?? 'N/A') . '</td><td><b>Job Name</b></td><td>' . $esc($job->job_name ?? 'N/A') . '</td></tr>'
                    . '<tr><td><b>Progress</b></td><td>' . $esc(($job->progress ?? 0) . '%') . '</td><td><b>Status</b></td><td style="font-weight:700;background:' . $statusBg . ';color:' . $statusFg . ';">' . $esc($formatStatus($status)) . '</td></tr>'
                    . '<tr><td><b>Started At</b></td><td>' . $esc($formatDate($job->started_at ?? null)) . '</td><td><b>Finished At</b></td><td>' . $esc($formatDate($job->finished_at ?? null)) . '</td></tr>'
                    . '<tr><td><b>Error</b></td><td colspan="3">' . $esc($job->error ?? 'None') . '</td></tr>'
                    . '<tr><td><b>Status Info</b></td><td colspan="3">' . $esc($job->status_info ?? 'N/A') . '</td></tr>'
                    . '<tr><td><b>User Name</b></td><td colspan="3">' . $esc($user->name ?? 'Merchant') . '</td></tr>'
                    . '<tr><td colspan="4" style="background:#f3f4f6;"><b>DETAILED PROCESSING RESULTS</b></td></tr>'
                    . '<tr><th>#</th><th>Txn ID</th><th>Result</th><th>Details</th></tr>';

                foreach ($rowResults as $result) {
                    $rStatus = strtoupper((string) ($result['status'] ?? 'N/A'));
                    $isSuccess = $rStatus === 'SUCCESS';
                    $bg = $isSuccess ? '#e8f5e9' : '#ffebee';
                    $fg = $isSuccess ? '#1b5e20' : '#b71c1c';
                    $html .= '<tr>'
                        . '<td>' . $esc($result['row'] ?? '') . '</td>'
                        . '<td>' . $esc($result['transaction_id'] ?? '') . '</td>'
                        . '<td style="font-weight:700;background:' . $bg . ';color:' . $fg . ';">' . $esc($rStatus) . '</td>'
                        . '<td>' . $esc($result['message'] ?? '') . '</td>'
                        . '</tr>';
                }

                $html .= '</table></body></html>';

                fwrite($file, $html);
            });

            return $this->fileLifecycleService->downloadAndDelete(
                $relativePath,
                $fileName,
                ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']
            );
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

    private function decodeRowResults(?string $rowResultsJson): array
    {
        if (! $rowResultsJson) {
            return [];
        }

        $decoded = json_decode($rowResultsJson, true);
        return is_array($decoded) ? $decoded : [];
    }
}


