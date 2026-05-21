<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Concerns\ValidatesBulkChargebackCsv;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessBulkChargebackJob;
use App\Services\FileLifecycleService;
use App\Traits\LogsConditionally;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BulkChargebacksController extends Controller
{
    use LogsConditionally;
    use ValidatesBulkChargebackCsv;

    public function __construct(
        protected FileLifecycleService $fileLifecycleService
    ) {}

    public function index(): View
    {
        return view('merchant.payments.bulk-chargebacks');
    }

    public function upload(Request $request): JsonResponse
    {
        try {
            $merchant = $request->user()->merchant;

            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:10240',
            ]);

            $file = $request->file('file');
            if (! $this->hasValidBulkChargebackHeaders($file->getRealPath())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file format - column mismatch with bulk chargeback template',
                ], 422);
            }

            $filePath = $this->fileLifecycleService->storeTempUpload($file, 'bulk_chargebacks');

            $job = DB::table('bulk_chargeback_jobs')->insertGetId([
                'job_name' => 'Bulk Chargeback Upload - '.basename($filePath),
                'file_path' => $filePath,
                'status' => 'pending',
                'progress' => 0,
                'started_at' => null,
                'user_id' => auth()->id(),
                'merchant_id' => $merchant->id,
                'test_mode' => (bool) $merchant->test_mode,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            ProcessBulkChargebackJob::dispatch($job, $filePath);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully. Processing started.',
                'job_id' => $job,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: '.$e->getMessage(),
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

            if ((bool) $merchant->test_mode) {
                $query->where(function ($q) {
                    $q->where('test_mode', true)
                        ->orWhereNull('test_mode');
                });
            } else {
                $query->where('test_mode', false);
            }

            if ($request->filled('filter_job_id')) {
                $query->where('id', 'like', '%'.$request->get('filter_job_id').'%');
            }
            if ($request->filled('filter_job_name')) {
                $query->where('job_name', 'like', '%'.$request->get('filter_job_name').'%');
            }
            if ($request->has('filter_status') && $request->get('filter_status') !== 'all') {
                $query->where('status', $request->get('filter_status'));
            }

            $jobs = $query->paginate($perPage);

            $data = collect($jobs->items())->map(function ($job) {
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
        $merchant = auth()->user()->merchant;
        $relativePath = $this->fileLifecycleService->createCsvReport('bulk_chargeback_template.csv', function ($file) use ($merchant): void {
            fputcsv($file, ['Chargeback Request ID', 'Merchant ID', 'Transaction ID', 'Amount', 'Status']);
            fputcsv($file, ['CB_SAMPLE_001', (string) $merchant->id, 'TXN_SAMPLE_001', '100.00', 'pending']);
        });

        return $this->fileLifecycleService->downloadAndDelete(
            $relativePath,
            'bulk_chargeback_template.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    public function downloadStatusFile(int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        try {
            $merchant = auth()->user()->merchant;
            if ((bool) $merchant->test_mode) {
                $job = DB::table('bulk_chargeback_jobs')
                    ->where('id', $id)
                    ->where('merchant_id', $merchant->id)
                    ->where(function ($q) {
                        $q->where('test_mode', true)
                            ->orWhereNull('test_mode');
                    })
                    ->first();
            } else {
                $job = DB::table('bulk_chargeback_jobs')
                    ->where('id', $id)
                    ->where('merchant_id', $merchant->id)
                    ->where('test_mode', false)
                    ->first();
            }

            if (! $job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found',
                ], 404);
            }

            $user = DB::table('users')->where('id', $job->user_id ?? null)->first();
            $rowResults = $this->decodeRowResults($job->row_results_json ?? null);
            $fileName = 'bulk_chargeback_job_'.$id.'_status.xls';

            $relativePath = $this->fileLifecycleService->createCsvReport($fileName, function ($file) use ($job, $user, $rowResults): void {
                $formatDate = static function ($value): string {
                    if (empty($value)) {
                        return 'N/A';
                    }

                    return date('d-m-Y H:i:s', strtotime((string) $value));
                };
                $esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
                $status = strtoupper((string) ($job->status ?? 'N/A'));
                $statusBg = match ($status) {
                    'COMPLETED' => '#e8f5e9',
                    'COMPLETED_WITH_ERRORS' => '#fff8e1',
                    'FAILED' => '#ffebee',
                    default => '#fff8e1',
                };
                $statusFg = match ($status) {
                    'COMPLETED' => '#1b5e20',
                    'COMPLETED_WITH_ERRORS' => '#8a6d1d',
                    'FAILED' => '#b71c1c',
                    default => '#8a6d1d',
                };

                $html = '<html><head><meta charset="UTF-8"></head><body>'
                    .'<table border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-family:Calibri,Arial,sans-serif;font-size:12px;">'
                    .'<tr><th colspan="4" style="background:#0d6efd;color:#fff;font-size:14px;text-align:left;">BULK CHARGEBACK STATUS REPORT</th></tr>'
                    .'<tr><td><b>Generated At</b></td><td colspan="3">'.$esc(now()->format('d-m-Y H:i:s')).'</td></tr>'
                    .'<tr><td colspan="4" style="background:#f3f4f6;"><b>JOB DETAILS</b></td></tr>'
                    .'<tr><td><b>Job ID</b></td><td>'.$esc($job->id ?? 'N/A').'</td><td><b>Job Name</b></td><td>'.$esc($job->job_name ?? 'N/A').'</td></tr>'
                    .'<tr><td><b>Progress</b></td><td>'.$esc(($job->progress ?? 0).'%').'</td><td><b>Status</b></td><td style="font-weight:700;background:'.$statusBg.';color:'.$statusFg.';">'.$esc(str_replace('_', ' ', $status)).'</td></tr>'
                    .'<tr><td><b>Started At</b></td><td>'.$esc($formatDate($job->started_at ?? null)).'</td><td><b>Finished At</b></td><td>'.$esc($formatDate($job->finished_at ?? null)).'</td></tr>'
                    .'<tr><td><b>Error</b></td><td colspan="3">'.$esc($job->error ?? 'None').'</td></tr>'
                    .'<tr><td><b>Status Info</b></td><td colspan="3">'.$esc($job->status_info ?? 'N/A').'</td></tr>'
                    .'<tr><td><b>User Name</b></td><td colspan="3">'.$esc($user->name ?? 'Merchant').'</td></tr>'
                    .'<tr><td colspan="4" style="background:#f3f4f6;"><b>ROW RESULTS</b></td></tr>'
                    .'<tr><th>#</th><th>Chargeback Request ID</th><th>Txn ID</th><th>Result / Details</th></tr>';

                foreach ($rowResults as $result) {
                    $rStatus = strtoupper((string) ($result['status'] ?? 'N/A'));
                    $ok = $rStatus === 'SUCCESS';
                    $bg = $ok ? '#e8f5e9' : '#ffebee';
                    $fg = $ok ? '#1b5e20' : '#b71c1c';
                    $html .= '<tr>'
                        .'<td>'.$esc($result['row'] ?? '').'</td>'
                        .'<td>'.$esc($result['chargeback_request_id'] ?? '').'</td>'
                        .'<td>'.$esc($result['transaction_id'] ?? '').'</td>'
                        .'<td style="font-weight:700;background:'.$bg.';color:'.$fg.';">'.$esc($rStatus.' — '.($result['message'] ?? '')).'</td>'
                        .'</tr>';
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
                'message' => 'Failed to generate status file',
            ], 500);
        }
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
