<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CsvProcessingService
{
    public function __construct(
        protected RefundService $refundService
    ) {
    }

    /**
     * Parse and process refund CSV rows in a single DB transaction.
     *
     * CSV format:
     * transaction_id,amount,reason
     */
    public function processRefundCsv(string $fullPath, int $initiatorUserId, ?int $merchantId = null): array
    {
        return DB::transaction(function () use ($fullPath, $initiatorUserId, $merchantId) {
            if (!is_file($fullPath)) {
                throw new \RuntimeException("CSV file not found: {$fullPath}");
            }

            $initiator = User::find($initiatorUserId);
            if (!$initiator) {
                throw new \RuntimeException("Initiator user not found: {$initiatorUserId}");
            }

            [$delimiter, $rows] = $this->readCsvRows($fullPath);
            if (count($rows) < 2) {
                throw new \RuntimeException('CSV is empty or missing data rows.');
            }

            $headers = $this->normalizeHeaders($rows[0]);
            $expected = ['transaction_id', 'amount', 'reason'];
            if ($headers !== $expected) {
                throw new \RuntimeException(
                    'Invalid CSV header. Expected: transaction_id,amount,reason'
                );
            }

            $processed = 0;
            $createdRefundIds = [];

            foreach (array_slice($rows, 1) as $i => $row) {
                $rowNo = $i + 2;
                if ($this->isBlankRow($row)) {
                    continue;
                }

                $transactionId = trim((string)($row[0] ?? ''));
                $amountRaw = trim((string)($row[1] ?? ''));
                $reason = trim((string)($row[2] ?? ''));

                if ($transactionId === '' || $amountRaw === '') {
                    throw new \RuntimeException("Row {$rowNo}: transaction_id and amount are required.");
                }

                $amount = filter_var($amountRaw, FILTER_VALIDATE_FLOAT);
                if ($amount === false || (float)$amount <= 0) {
                    throw new \RuntimeException("Row {$rowNo}: invalid amount '{$amountRaw}'.");
                }

                $refund = $this->refundService->createRefundByTransactionId(
                    $initiator,
                    $transactionId,
                    (float)$amount,
                    $reason !== '' ? $reason : null,
                    $merchantId,
                    $merchantId ? (bool) optional($initiator->merchant)->test_mode : null
                );

                $processed++;
                $createdRefundIds[] = $refund->refund_id;
            }

            Log::info('CSV processed successfully', [
                'file' => $fullPath,
                'delimiter' => $delimiter,
                'processed_rows' => $processed,
                'refund_ids' => $createdRefundIds,
                'merchant_id' => $merchantId,
                'initiator_user_id' => $initiatorUserId,
            ]);

            return [
                'processed_rows' => $processed,
                'refund_ids' => $createdRefundIds,
            ];
        });
    }

    /**
     * @return array{0:string,1:array<int, array<int, string>>}
     */
    protected function readCsvRows(string $fullPath): array
    {
        $handle = fopen($fullPath, 'r');
        if (!$handle) {
            throw new \RuntimeException("Unable to open CSV file: {$fullPath}");
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            throw new \RuntimeException('CSV file is empty.');
        }

        $firstLine = trim($firstLine);
        $delimiter = ',';

        if (stripos($firstLine, 'sep=') === 0) {
            $delimiter = substr($firstLine, 4, 1) ?: ',';
        } else {
            $comma = substr_count($firstLine, ',');
            $semi = substr_count($firstLine, ';');
            $delimiter = $semi > $comma ? ';' : ',';
            rewind($handle);
        }

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map(static fn($v) => trim((string)$v), $row);
        }
        fclose($handle);

        return [$delimiter, $rows];
    }

    /**
     * @param array<int,string> $header
     * @return array<int,string>
     */
    protected function normalizeHeaders(array $header): array
    {
        return array_map(static function ($v) {
            $value = trim((string)$v);
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
            return strtolower($value);
        }, $header);
    }

    /**
     * @param array<int,string> $row
     */
    protected function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string)$value) !== '') {
                return false;
            }
        }
        return true;
    }
}

