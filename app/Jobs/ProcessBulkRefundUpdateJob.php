<?php

namespace App\Jobs;

use App\Models\Refund;
use App\Models\Transaction;
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
use App\Services\FileLifecycleService;

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
        /** @var FileLifecycleService $fileLifecycle */
        $fileLifecycle = app(FileLifecycleService::class);

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
            $pendingApprovalCount = 0;
            $pendingProcessingCount = 0;
            $errorMessages = [];
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
                    $message = 'transaction_id and amount are required';
                    $results[] = [
                        'row' => $rowNumber,
                        'transaction_id' => $transactionId,
                        'status' => 'FAILED',
                        'message' => $message,
                    ];
                    $errorMessages[] = "Row {$rowNumber}: {$message}";
                    $errorCount++;
                    continue;
                }

                // Accept common CSV numeric formats like "15,000.00"
                $normalizedAmountRaw = str_replace(',', '', $amountRaw);
                $amount = filter_var($normalizedAmountRaw, FILTER_VALIDATE_FLOAT);
                if ($amount === false || (float) $amount <= 0) {
                    $message = "Invalid amount: {$amountRaw}";
                    $results[] = [
                        'row' => $rowNumber,
                        'transaction_id' => $transactionId,
                        'status' => 'FAILED',
                        'message' => $message,
                    ];
                    $errorMessages[] = "Row {$rowNumber}: {$message}";
                    $errorCount++;
                    continue;
                }

                $dupKey = strtolower($transactionId) . '|' . number_format((float) $amount, 2, '.', '') . '|' . strtolower($reason);
                if (isset($seenKeys[$dupKey])) {
                    $message = 'Duplicate row detected';
                    $results[] = [
                        'row' => $rowNumber,
                        'transaction_id' => $transactionId,
                        'status' => 'FAILED',
                        'message' => $message,
                    ];
                    $errorMessages[] = "Row {$rowNumber}: {$message}";
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
                        isset($job->test_mode) ? (bool) $job->test_mode : (isset($job->merchant_id) && $job->merchant_id ? (bool) optional($initiator->merchant)->test_mode : null)
                    );

                    if ($refund->status === 'completed') {
                        $results[] = [
                            'row' => $rowNumber,
                            'transaction_id' => $transactionId,
                            'status' => 'SUCCESS',
                            'message' => 'Refund created successfully',
                        ];
                        $successCount++;
                    } elseif ($refund->status === 'pending_approval') {
                        $results[] = [
                            'row' => $rowNumber,
                            'transaction_id' => $transactionId,
                            'status' => 'PENDING_APPROVAL',
                            'message' => 'Refund request submitted and pending admin approval',
                        ];
                        $pendingApprovalCount++;
                    } elseif (in_array($refund->status, ['pending_processing', 'processing'], true)) {
                        $results[] = [
                            'row' => $rowNumber,
                            'transaction_id' => $transactionId,
                            'status' => 'PENDING_PROCESSING',
                            'message' => 'Refund initiated and pending gateway processing',
                        ];
                        $pendingProcessingCount++;
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
                    $message = 'Transaction not found';
                    $results[] = [
                        'row' => $rowNumber,
                        'transaction_id' => $transactionId,
                        'status' => 'FAILED',
                        'message' => $message,
                    ];
                    $errorMessages[] = "Row {$rowNumber}: {$message}";
                    $errorCount++;
                } catch (\Exception $e) {
                    $message = $this->normalizeRefundErrorMessage($e->getMessage());

                    if ($message === 'Already refunded (max refundable: 0)') {
                        $existingStatus = $this->resolveExistingRefundStatus($transactionId, $job);
                        if ($existingStatus === 'pending_approval') {
                            $results[] = [
                                'row' => $rowNumber,
                                'transaction_id' => $transactionId,
                                'status' => 'PENDING_APPROVAL',
                                'message' => 'Refund already requested and pending admin approval',
                            ];
                            $pendingApprovalCount++;
                            continue;
                        }
                        if (in_array($existingStatus, ['pending_processing', 'processing'], true)) {
                            $results[] = [
                                'row' => $rowNumber,
                                'transaction_id' => $transactionId,
                                'status' => 'PENDING_PROCESSING',
                                'message' => 'Refund already initiated and pending gateway processing',
                            ];
                            $pendingProcessingCount++;
                            continue;
                        }
                        if ($existingStatus === 'completed') {
                            $results[] = [
                                'row' => $rowNumber,
                                'transaction_id' => $transactionId,
                                'status' => 'ALREADY_REFUNDED',
                                'message' => 'Refund already completed earlier',
                            ];
                            $successCount++;
                            continue;
                        }
                    }

                    $results[] = [
                        'row' => $rowNumber,
                        'transaction_id' => $transactionId,
                        'status' => 'FAILED',
                        'message' => $message,
                    ];
                    $errorMessages[] = "Row {$rowNumber}: {$message}";
                    $errorCount++;
                }

                $progress = (int) ((($idx + 1) / max(1, $totalRows)) * 100);
                DB::table('bulk_refund_jobs')
                    ->where('id', $this->jobId)
                    ->update(['progress' => min($progress, 99)]);
            }

            // Update job status
            $finalStatus = $errorCount > 0 ? 'completed_with_errors' : 'completed';
            $statusInfo = "Processed: {$totalRows} rows | Success: {$successCount} | Pending approval: {$pendingApprovalCount} | Pending processing: {$pendingProcessingCount} | Errors: {$errorCount}";
            $finishedAt = now();

            DB::table('bulk_refund_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => $finalStatus,
                    'progress' => 100,
                    'finished_at' => $finishedAt,
                    'status_info' => $statusInfo,
                    'error' => $errorCount > 0 ? implode(' | ', array_slice($errorMessages, 0, 5)) : null,
                    'row_results_json' => json_encode($results, JSON_UNESCAPED_UNICODE),
                ]);

            Log::info("Bulk refund upload job {$this->jobId} completed", [
                'total_rows' => $totalRows,
                'success_count' => $successCount,
                'error_count' => $errorCount,
            ]);

            // Lifecycle cleanup: success => delete uploaded temp file.
            $fileLifecycle->deleteIfExists($this->filePath);

        } catch (\Exception $e) {
            // Update job status to failed
            DB::table('bulk_refund_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => 'failed',
                    'progress' => 0,
                    'finished_at' => now(),
                    'error' => $e->getMessage(),
                    'row_results_json' => json_encode([[
                        'row' => 0,
                        'transaction_id' => '',
                        'status' => 'FAILED',
                        'message' => $e->getMessage(),
                    ]], JSON_UNESCAPED_UNICODE),
                ]);

            Log::error("Bulk refund upload job {$this->jobId} failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Lifecycle cleanup: failure => preserve input in failed_uploads for debugging.
            $fileLifecycle->moveToFailedUploads($this->filePath, $e->getMessage());

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

    private function normalizeRefundErrorMessage(string $message): string
    {
        if (stripos($message, 'Refund amount exceeds refundable amount. Maximum: 0') !== false) {
            return 'Already refunded (max refundable: 0)';
        }

        return $message;
    }

    private function resolveExistingRefundStatus(string $transactionId, object $job): ?string
    {
        $txnQuery = Transaction::query()->where('txn_id', $transactionId);
        if (isset($job->merchant_id) && $job->merchant_id) {
            $txnQuery->where('merchant_id', (int) $job->merchant_id);
        }
        if (isset($job->test_mode)) {
            $txnQuery->where('test_mode', (bool) $job->test_mode);
        }

        $transaction = $txnQuery->first();
        if (! $transaction) {
            return null;
        }

        $refund = Refund::query()
            ->where('transaction_id', $transaction->id)
            ->orderByDesc('id')
            ->first();

        return $refund?->status;
    }
}

