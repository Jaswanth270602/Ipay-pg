<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\RefundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessBulkRefundUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobId;
    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(int $jobId, string $filePath)
    {
        $this->jobId = $jobId;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(RefundService $refundService): void
    {
        try {
            // Update job status to processing
            DB::table('bulk_refund_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => 'processing',
                    'started_at' => now(),
                ]);

            $fullPath = Storage::disk('local')->path($this->filePath);
            
            // Check if file exists
            if (!file_exists($fullPath)) {
                throw new \Exception("File not found: {$fullPath}");
            }

            $rows = $this->readCsvRows($fullPath);
            if (count($rows) < 1) {
                throw new \Exception('Invalid file format - column mismatch with refund form');
            }

            $headers = array_map(static function ($h) {
                $value = trim((string) $h);
                $value = preg_replace('/^\xEF\xBB\xBF/', '', $value); // strip UTF-8 BOM
                return strtolower($value);
            }, $rows[0]);
            $expectedHeaders = ['transaction_id', 'amount', 'reason'];
            if ($headers !== $expectedHeaders) {
                throw new \Exception('Invalid file format - column mismatch with refund form');
            }

            $job = DB::table('bulk_refund_jobs')->where('id', $this->jobId)->first();
            $initiator = User::find($job->user_id ?? 0);
            if (!$initiator) {
                throw new \Exception('Unable to resolve refund initiator for this job');
            }

            $results = [];
            $successCount = 0;
            $errorCount = 0;
            $dataRows = array_slice($rows, 1);
            $totalRows = count($dataRows);
            $seenKeys = [];

            foreach ($dataRows as $idx => $row) {
                $rowNumber = $idx + 2; // include header line

                if (empty(array_filter($row, static fn($v) => trim((string) $v) !== ''))) {
                    continue;
                }

                $transactionId = trim((string) ($row[0] ?? ''));
                $amountRaw = trim((string) ($row[1] ?? ''));
                $reason = trim((string) ($row[2] ?? ''));

                if ($transactionId === '' || $amountRaw === '') {
                    $results[] = [
                        'row' => $rowNumber,
                        'transaction_id' => $transactionId,
                        'status' => 'FAILED',
                        'message' => 'transaction_id and amount are required',
                    ];
                    $errorCount++;
                    continue;
                }

                $amount = filter_var($amountRaw, FILTER_VALIDATE_FLOAT);
                if ($amount === false || (float) $amount <= 0) {
                    $results[] = [
                        'row' => $rowNumber,
                        'transaction_id' => $transactionId,
                        'status' => 'FAILED',
                        'message' => "Invalid amount: {$amountRaw}",
                    ];
                    $errorCount++;
                    continue;
                }

                $dupKey = strtolower($transactionId) . '|' . number_format((float) $amount, 2, '.', '') . '|' . strtolower($reason);
                if (isset($seenKeys[$dupKey])) {
                    $results[] = [
                        'row' => $rowNumber,
                        'transaction_id' => $transactionId,
                        'status' => 'FAILED',
                        'message' => 'Duplicate row detected',
                    ];
                    $errorCount++;
                    continue;
                }
                $seenKeys[$dupKey] = true;

                try {
                    $refund = $refundService->createRefundByTransactionId(
                        $initiator,
                        $transactionId,
                        (float) $amount,
                        $reason !== '' ? $reason : null,
                        $job->merchant_id ?? null,
                        isset($job->merchant_id) && $job->merchant_id ? (bool) optional($initiator->merchant)->test_mode : null
                    );

                    if ($refund->status === 'completed') {
                        $results[] = [
                            'row' => $rowNumber,
                            'transaction_id' => $transactionId,
                            'status' => 'SUCCESS',
                            'message' => 'Refund created successfully',
                        ];
                        $successCount++;
                    } else {
                        $results[] = [
                            'row' => $rowNumber,
                            'transaction_id' => $transactionId,
                            'status' => 'FAILED',
                            'message' => $refund->gateway_response['message'] ?? $refund->gateway_response['error'] ?? 'Refund could not be processed',
                        ];
                        $errorCount++;
                    }
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    $results[] = [
                        'row' => $rowNumber,
                        'transaction_id' => $transactionId,
                        'status' => 'FAILED',
                        'message' => 'Transaction not found',
                    ];
                    $errorCount++;
                } catch (\Exception $e) {
                    $results[] = [
                        'row' => $rowNumber,
                        'transaction_id' => $transactionId,
                        'status' => 'FAILED',
                        'message' => $e->getMessage(),
                    ];
                    $errorCount++;
                }

                $progress = (int) ((($idx + 1) / max(1, $totalRows)) * 100);
                DB::table('bulk_refund_jobs')
                    ->where('id', $this->jobId)
                    ->update(['progress' => min($progress, 99)]);
            }

            // Update job status
            $finalStatus = $errorCount > 0 && $successCount === 0 ? 'failed' : 'completed';
            $statusInfo = "Processed: {$totalRows} rows | Success: {$successCount} | Errors: {$errorCount}";
            $finishedAt = now();

            // Prepare export file path first so we can persist it with final status.
            $exportFileName = 'bulk_refund_status_' . $this->jobId . '_' . time() . '.xls';
            $exportPath = 'bulk_refunds/export/' . $exportFileName;
            $exportFullPath = Storage::disk('local')->path($exportPath);
            $exportDir = dirname($exportFullPath);
            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0755, true);
            }

            DB::table('bulk_refund_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => $finalStatus,
                    'progress' => 100,
                    'finished_at' => $finishedAt,
                    'export_file_path' => $exportPath,
                    'status_info' => $statusInfo,
                ]);

            // Read fresh values (including finished_at) and build report.
            $job = DB::table('bulk_refund_jobs')->where('id', $this->jobId)->first();
            $user = DB::table('users')->where('id', $job->user_id ?? null)->first();
            $html = $this->buildStyledStatusReportHtml($job, $user, $results, $totalRows, $successCount, $errorCount);
            file_put_contents($exportFullPath, $html);

            Log::info("Bulk refund upload job {$this->jobId} completed", [
                'total_rows' => $totalRows,
                'success_count' => $successCount,
                'error_count' => $errorCount,
            ]);

        } catch (\Exception $e) {
            // Update job status to failed
            DB::table('bulk_refund_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => 'failed',
                    'progress' => 0,
                    'finished_at' => now(),
                    'error' => $e->getMessage(),
                ]);

            Log::error("Bulk refund upload job {$this->jobId} failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readCsvRows(string $fullPath): array
    {
        $delimiter = $this->detectCsvDelimiter($fullPath);
        if (!$delimiter) {
            throw new \Exception("Cannot detect CSV delimiter for file: {$fullPath}");
        }

        $rows = [];
        $handle = fopen($fullPath, 'r');
        if (!$handle) {
            throw new \Exception("Cannot open file: {$fullPath}");
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map(static fn($v) => trim((string) $v), $row);
        }

        fclose($handle);
        return $rows;
    }

    private function detectCsvDelimiter(string $path): ?string
    {
        $h = @fopen($path, 'r');
        if (!$h) {
            return null;
        }
        $firstLine = fgets($h);
        fclose($h);
        if ($firstLine === false) {
            return null;
        }

        $commaCount = substr_count($firstLine, ',');
        $semiCount = substr_count($firstLine, ';');

        return $semiCount > $commaCount ? ';' : ',';
    }

    private function buildStyledStatusReportHtml(object $job, ?object $user, array $results, int $totalRows, int $successCount, int $errorCount): string
    {
        $fmtDate = static function ($value): string {
            if (empty($value)) {
                return 'N/A';
            }
            return date('d-m-Y H:i:s', strtotime((string) $value));
        };
        $esc = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $jobStatus = strtoupper((string) ($job->status ?? 'N/A'));
        $statusBg = in_array($jobStatus, ['COMPLETED', 'SUCCESS'], true) ? '#e8f5e9' : ($jobStatus === 'FAILED' ? '#ffebee' : '#fff8e1');
        $statusFg = in_array($jobStatus, ['COMPLETED', 'SUCCESS'], true) ? '#1b5e20' : ($jobStatus === 'FAILED' ? '#b71c1c' : '#8a6d1d');

        $rowsHtml = '';
        foreach ($results as $result) {
            $status = strtoupper((string) ($result['status'] ?? 'N/A'));
            $isSuccess = $status === 'SUCCESS';
            $bg = $isSuccess ? '#e8f5e9' : '#ffebee';
            $fg = $isSuccess ? '#1b5e20' : '#b71c1c';
            $rowsHtml .= '<tr>'
                . '<td>' . $esc($result['row'] ?? '') . '</td>'
                . '<td>' . $esc($result['transaction_id'] ?? '') . '</td>'
                . '<td style="font-weight:700;background:' . $bg . ';color:' . $fg . ';">' . $esc($status) . '</td>'
                . '<td>' . $esc($result['message'] ?? '') . '</td>'
                . '</tr>';
        }

        return '<html><head><meta charset="UTF-8"></head><body>'
            . '<table border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;font-family:Calibri,Arial,sans-serif;font-size:12px;">'
            . '<tr><th colspan="4" style="background:#0d6efd;color:#fff;font-size:14px;text-align:left;">BULK REFUND STATUS REPORT</th></tr>'
            . '<tr><td><b>Generated At</b></td><td colspan="3">' . $esc(now()->format('d-m-Y H:i:s')) . '</td></tr>'
            . '<tr><td colspan="4" style="background:#f3f4f6;"><b>JOB DETAILS</b></td></tr>'
            . '<tr><td><b>Job ID</b></td><td>' . $esc($job->id ?? 'N/A') . '</td><td><b>Job Name</b></td><td>' . $esc($job->job_name ?? 'N/A') . '</td></tr>'
            . '<tr><td><b>Progress</b></td><td>' . $esc(($job->progress ?? 0) . '%') . '</td><td><b>Status</b></td><td style="font-weight:700;background:' . $statusBg . ';color:' . $statusFg . ';">' . $esc($jobStatus) . '</td></tr>'
            . '<tr><td><b>Started At</b></td><td>' . $esc($fmtDate($job->started_at ?? null)) . '</td><td><b>Finished At</b></td><td>' . $esc($fmtDate($job->finished_at ?? null)) . '</td></tr>'
            . '<tr><td><b>Error</b></td><td colspan="3">' . $esc($job->error ?? 'None') . '</td></tr>'
            . '<tr><td><b>Status Info</b></td><td colspan="3">' . $esc($job->status_info ?? 'N/A') . '</td></tr>'
            . '<tr><td><b>User Name</b></td><td colspan="3">' . $esc($user->name ?? 'Admin') . '</td></tr>'
            . '<tr><td colspan="4" style="background:#f3f4f6;"><b>PROCESSING SUMMARY</b></td></tr>'
            . '<tr><td><b>Total Rows</b></td><td>' . $esc($totalRows) . '</td><td><b>Successful</b></td><td style="background:#e8f5e9;color:#1b5e20;font-weight:700;">' . $esc($successCount) . '</td></tr>'
            . '<tr><td><b>Errors</b></td><td style="background:#ffebee;color:#b71c1c;font-weight:700;">' . $esc($errorCount) . '</td><td><b>Skipped</b></td><td>' . $esc(count($results) - $successCount - $errorCount) . '</td></tr>'
            . '<tr><td colspan="4" style="background:#f3f4f6;"><b>DETAILED PROCESSING RESULTS</b></td></tr>'
            . '<tr><th>#</th><th>Txn ID</th><th>Result</th><th>Details</th></tr>'
            . $rowsHtml
            . '</table></body></html>';
    }
}

