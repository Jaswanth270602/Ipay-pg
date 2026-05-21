<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\FileLifecycleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProcessBulkChargebackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $jobId,
        protected string $filePath
    ) {}

    public function handle(): void
    {
        /** @var FileLifecycleService $fileLifecycle */
        $fileLifecycle = app(FileLifecycleService::class);

        try {
            DB::table('bulk_chargeback_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => 'processing',
                    'started_at' => now(),
                ]);

            $fullPath = Storage::disk('local')->path($this->filePath);
            if (! file_exists($fullPath)) {
                throw new \Exception("File not found: {$fullPath}");
            }

            $rows = $this->readCsvRows($fullPath);
            if (count($rows) < 1) {
                throw new \Exception('Invalid file format - empty or unreadable CSV');
            }

            $headers = array_map(static function ($h) {
                $value = trim((string) $h);
                $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
                $value = strtolower($value);

                return str_replace(' ', '_', preg_replace('/[^a-z0-9 ]/i', '', $value));
            }, $rows[0]);

            $expected = ['chargeback_request_id', 'merchant_id', 'transaction_id', 'amount', 'status'];
            if ($headers !== $expected) {
                throw new \Exception('Invalid file format - column mismatch with bulk chargeback template');
            }

            $jobRow = DB::table('bulk_chargeback_jobs')->where('id', $this->jobId)->first();
            if (! $jobRow) {
                throw new \Exception('Bulk chargeback job record not found');
            }

            $hasTestModeOnChargebacks = Schema::hasTable('chargebacks') && Schema::hasColumn('chargebacks', 'test_mode');

            $results = [];
            $successCount = 0;
            $errorCount = 0;
            $errorMessages = [];
            $dataRows = array_slice($rows, 1);
            $totalRows = count($dataRows);

            foreach ($dataRows as $idx => $row) {
                $rowNumber = $idx + 2;
                if (empty(array_filter($row, static fn ($v) => trim((string) $v) !== ''))) {
                    continue;
                }

                $requestId = trim((string) ($row[0] ?? ''));
                $csvMerchantIdRaw = trim((string) ($row[1] ?? ''));
                $txnTxt = trim((string) ($row[2] ?? ''));
                $amountRaw = trim((string) ($row[3] ?? ''));
                $statusRaw = trim((string) ($row[4] ?? ''));

                if ($requestId === '' || $txnTxt === '' || $amountRaw === '') {
                    $message = 'Chargeback Request ID, Transaction ID, and Amount are required';
                    $results[] = $this->rowResult($rowNumber, $requestId, $txnTxt, 'FAILED', $message);
                    $errorMessages[] = "Row {$rowNumber}: {$message}";
                    $errorCount++;

                    continue;
                }

                $merchantIdForRow = $this->resolveMerchantIdForRow($jobRow, $csvMerchantIdRaw, $rowNumber, $results, $errorMessages);
                if ($merchantIdForRow === null) {
                    $errorCount++;

                    continue;
                }

                $normalizedAmountRaw = str_replace(',', '', $amountRaw);
                $amount = filter_var($normalizedAmountRaw, FILTER_VALIDATE_FLOAT);
                if ($amount === false || (float) $amount <= 0) {
                    $message = "Invalid amount: {$amountRaw}";
                    $results[] = $this->rowResult($rowNumber, $requestId, $txnTxt, 'FAILED', $message);
                    $errorMessages[] = "Row {$rowNumber}: {$message}";
                    $errorCount++;

                    continue;
                }

                $chargebackStatus = $this->normalizeChargebackStatus($statusRaw);

                if (DB::table('chargebacks')->where('chargeback_request_id', $requestId)->exists()) {
                    $message = 'Duplicate chargeback_request_id';
                    $results[] = $this->rowResult($rowNumber, $requestId, $txnTxt, 'FAILED', $message);
                    $errorMessages[] = "Row {$rowNumber}: {$message}";
                    $errorCount++;

                    continue;
                }

                $txnQuery = Transaction::query()
                    ->where('txn_id', $txnTxt)
                    ->where('merchant_id', $merchantIdForRow);
                if ($jobRow->test_mode !== null) {
                    $txnQuery->where('test_mode', (bool) $jobRow->test_mode);
                }
                $transaction = $txnQuery->first();
                if (! $transaction) {
                    $message = 'Transaction not found for txn_id / merchant (and test mode scope if set)';
                    $results[] = $this->rowResult($rowNumber, $requestId, $txnTxt, 'FAILED', $message);
                    $errorMessages[] = "Row {$rowNumber}: {$message}";
                    $errorCount++;

                    continue;
                }

                try {
                    $insert = [
                        'merchant_id' => $merchantIdForRow,
                        'transaction_id' => $transaction->id,
                        'chargeback_request_id' => $requestId,
                        'chargeback_amount' => round((float) $amount, 2),
                        'chargeback_status' => $chargebackStatus,
                        'notes' => 'Created from bulk upload job #'.$this->jobId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    if ($hasTestModeOnChargebacks) {
                        $insert['test_mode'] = (bool) $transaction->test_mode;
                    }
                    DB::table('chargebacks')->insert($insert);

                    $results[] = $this->rowResult($rowNumber, $requestId, $txnTxt, 'SUCCESS', 'Chargeback record created');
                    $successCount++;
                } catch (\Throwable $e) {
                    $message = $e->getMessage();
                    $results[] = $this->rowResult($rowNumber, $requestId, $txnTxt, 'FAILED', $message);
                    $errorMessages[] = "Row {$rowNumber}: {$message}";
                    $errorCount++;
                }

                $progress = (int) ((($idx + 1) / max(1, $totalRows)) * 100);
                DB::table('bulk_chargeback_jobs')
                    ->where('id', $this->jobId)
                    ->update(['progress' => min($progress, 99)]);
            }

            $finalStatus = $errorCount > 0 ? 'completed_with_errors' : 'completed';
            $statusInfo = "Processed rows: {$totalRows} | Success: {$successCount} | Errors: {$errorCount}";

            DB::table('bulk_chargeback_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => $finalStatus,
                    'progress' => 100,
                    'finished_at' => now(),
                    'status_info' => $statusInfo,
                    'error' => $errorCount > 0 ? implode(' | ', array_slice($errorMessages, 0, 5)) : null,
                    'row_results_json' => json_encode($results, JSON_UNESCAPED_UNICODE),
                ]);

            Log::info('Bulk chargeback upload job completed', [
                'job_id' => $this->jobId,
                'total_rows' => $totalRows,
                'success_count' => $successCount,
                'error_count' => $errorCount,
            ]);

            $fileLifecycle->deleteIfExists($this->filePath);
        } catch (\Exception $e) {
            DB::table('bulk_chargeback_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => 'failed',
                    'progress' => 0,
                    'finished_at' => now(),
                    'error' => $e->getMessage(),
                    'status_info' => null,
                    'row_results_json' => json_encode([[
                        'row' => 0,
                        'chargeback_request_id' => '',
                        'transaction_id' => '',
                        'status' => 'FAILED',
                        'message' => $e->getMessage(),
                    ]], JSON_UNESCAPED_UNICODE),
                ]);

            Log::error('Bulk chargeback upload job failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $fileLifecycle->moveToFailedUploads($this->filePath, $e->getMessage());

            throw $e;
        }
    }

    /**
     * @param  array<int, mixed>  $results
     * @param  list<string>  $errorMessages
     */
    private function resolveMerchantIdForRow(object $jobRow, string $csvMerchantIdRaw, int $rowNumber, array &$results, array &$errorMessages): ?int
    {
        if ($jobRow->merchant_id !== null) {
            $jobMid = (int) $jobRow->merchant_id;
            if ($csvMerchantIdRaw !== '' && (int) $csvMerchantIdRaw !== $jobMid) {
                $message = 'Merchant ID in file must match your merchant account';
                $results[] = $this->rowResult($rowNumber, '', '', 'FAILED', $message);
                $errorMessages[] = "Row {$rowNumber}: {$message}";

                return null;
            }

            return $jobMid;
        }

        if ($csvMerchantIdRaw === '' || ! ctype_digit($csvMerchantIdRaw)) {
            $message = 'Merchant ID is required for admin uploads';
            $results[] = $this->rowResult($rowNumber, '', '', 'FAILED', $message);
            $errorMessages[] = "Row {$rowNumber}: {$message}";

            return null;
        }

        $mid = (int) $csvMerchantIdRaw;
        if (! DB::table('merchants')->where('id', $mid)->exists()) {
            $message = 'Invalid Merchant ID';
            $results[] = $this->rowResult($rowNumber, '', '', 'FAILED', $message);
            $errorMessages[] = "Row {$rowNumber}: {$message}";

            return null;
        }

        return $mid;
    }

    /**
     * @return array{row:int,chargeback_request_id:string,transaction_id:string,status:string,message:string}
     */
    private function rowResult(int $rowNumber, string $requestId, string $txnTxt, string $status, string $message): array
    {
        return [
            'row' => $rowNumber,
            'chargeback_request_id' => $requestId,
            'transaction_id' => $txnTxt,
            'status' => $status,
            'message' => $message,
        ];
    }

    private function normalizeChargebackStatus(string $raw): string
    {
        $v = strtolower(trim($raw));
        $allowed = ['pending', 'contested', 'won', 'lost', 'processing'];

        return in_array($v, $allowed, true) ? $v : 'pending';
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readCsvRows(string $fullPath): array
    {
        $delimiter = $this->detectCsvDelimiter($fullPath);
        if (! $delimiter) {
            throw new \Exception("Cannot detect CSV delimiter for file: {$fullPath}");
        }

        $rows = [];
        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            throw new \Exception("Cannot open file: {$fullPath}");
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map(static fn ($v) => trim((string) $v), $row);
        }

        fclose($handle);

        return $rows;
    }

    private function detectCsvDelimiter(string $path): ?string
    {
        $handle = @fopen($path, 'r');
        if ($handle === false) {
            return null;
        }
        $firstLine = fgets($handle);
        fclose($handle);
        if ($firstLine === false) {
            return null;
        }

        $commaCount = substr_count($firstLine, ',');
        $semiCount = substr_count($firstLine, ';');

        return $semiCount > $commaCount ? ';' : ',';
    }
}
