<?php

namespace App\Http\Controllers\Concerns;

/**
 * Shared CSV header validation for bulk chargeback uploads (matches template columns).
 */
trait ValidatesBulkChargebackCsv
{
    /**
     * @return list<string>
     */
    private function bulkChargebackExpectedHeaders(): array
    {
        return ['chargeback_request_id', 'merchant_id', 'transaction_id', 'amount', 'status'];
    }

    private function normalizeChargebackCsvHeaderCell(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        $value = strtolower($value);

        return str_replace(' ', '_', preg_replace('/[^a-z0-9 ]/i', '', $value));
    }

    protected function hasValidBulkChargebackHeaders(string $path): bool
    {
        $handle = @fopen($path, 'r');
        if ($handle === false) {
            return false;
        }

        [, $header] = $this->readBulkChargebackHeaderRow($handle);
        fclose($handle);

        if (! is_array($header)) {
            return false;
        }

        $normalized = array_map(fn ($v) => $this->normalizeChargebackCsvHeaderCell((string) $v), $header);

        return $normalized === $this->bulkChargebackExpectedHeaders();
    }

    /**
     * @param  resource  $handle
     * @return array{0:string,1:array<int,string>|null}
     */
    private function readBulkChargebackHeaderRow($handle): array
    {
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            return [',', null];
        }

        $firstLineTrim = trim($firstLine);

        // Excel may prepend "sep=," or "sep=;" on line 1.
        if (stripos($firstLineTrim, 'sep=') === 0) {
            $delimiter = substr($firstLineTrim, 4, 1) ?: ',';
            $header = fgetcsv($handle, 0, $delimiter);

            return [$delimiter, $header ?: null];
        }

        $commaCount = substr_count($firstLineTrim, ',');
        $semiCount = substr_count($firstLineTrim, ';');
        $delimiter = $semiCount > $commaCount ? ';' : ',';

        rewind($handle);
        $header = fgetcsv($handle, 0, $delimiter);

        return [$delimiter, $header ?: null];
    }
}
