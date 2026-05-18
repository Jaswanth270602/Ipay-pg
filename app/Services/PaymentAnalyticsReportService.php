<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\Settlement;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PaymentAnalyticsReportService
{
    public const REPORT_TRANSACTION_SUMMARY = 'transaction_summary';

    public const REPORT_PAYMENT_METHOD = 'payment_method';

    public const REPORT_SUCCESS_RATE = 'success_rate';

    public const REPORT_REFUND_SUMMARY = 'refund_summary';

    public const REPORT_SETTLEMENT_SUMMARY = 'settlement_summary';

    public const REPORT_DAILY_TREND = 'daily_trend';

    /** @return list<string> */
    public static function reportTypes(): array
    {
        return [
            self::REPORT_TRANSACTION_SUMMARY,
            self::REPORT_PAYMENT_METHOD,
            self::REPORT_SUCCESS_RATE,
            self::REPORT_REFUND_SUMMARY,
            self::REPORT_SETTLEMENT_SUMMARY,
            self::REPORT_DAILY_TREND,
        ];
    }

    public static function reportLabels(): array
    {
        return [
            self::REPORT_TRANSACTION_SUMMARY => 'Transaction summary',
            self::REPORT_PAYMENT_METHOD => 'Payment method breakdown',
            self::REPORT_SUCCESS_RATE => 'Success rate (by method)',
            self::REPORT_REFUND_SUMMARY => 'Refund summary',
            self::REPORT_SETTLEMENT_SUMMARY => 'Settlement summary',
            self::REPORT_DAILY_TREND => 'Daily volume trend',
        ];
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function parseRange(?string $fromDate, ?string $toDate): array
    {
        $from = $fromDate ? Carbon::parse($fromDate)->startOfDay() : null;
        $to = $toDate ? Carbon::parse($toDate)->endOfDay() : null;

        return [$from, $to];
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $reportType, bool $isTestMode, ?int $merchantId, ?string $fromDate, ?string $toDate): array
    {
        if (! in_array($reportType, self::reportTypes(), true)) {
            throw new \InvalidArgumentException('Unknown report type: ' . $reportType);
        }

        [$from, $to] = $this->parseRange($fromDate, $toDate);

        return match ($reportType) {
            self::REPORT_TRANSACTION_SUMMARY => $this->transactionSummary($isTestMode, $merchantId, $from, $to),
            self::REPORT_PAYMENT_METHOD => $this->paymentMethodBreakdown($isTestMode, $merchantId, $from, $to),
            self::REPORT_SUCCESS_RATE => $this->successRateByMethod($isTestMode, $merchantId, $from, $to),
            self::REPORT_REFUND_SUMMARY => $this->refundSummary($isTestMode, $merchantId, $from, $to),
            self::REPORT_SETTLEMENT_SUMMARY => $this->settlementSummary($isTestMode, $merchantId, $from, $to),
            self::REPORT_DAILY_TREND => $this->dailyTrend($isTestMode, $merchantId, $from, $to),
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function transactionSummary(bool $isTestMode, ?int $merchantId, ?Carbon $from, ?Carbon $to): array
    {
        $base = $this->transactionQuery($isTestMode, $merchantId, $from, $to);
        $total = (clone $base)->count();
        $successful = (clone $base)->where('status', 'success')->count();
        $failed = (clone $base)->where('status', 'failed')->count();
        $pending = (clone $base)->whereIn('status', ['pending', 'initiated', 'authorized', 'captured'])->count();
        $volume = (float) (clone $base)->where('status', 'success')->sum('amount');
        $fees = (float) (clone $base)->where('status', 'success')->sum('fee_amount');
        $net = (float) (clone $base)->where('status', 'success')->sum('net_amount');

        return [
            'report_type' => self::REPORT_TRANSACTION_SUMMARY,
            'summary' => [
                'total_attempts' => $total,
                'successful_count' => $successful,
                'failed_count' => $failed,
                'pending_count' => $pending,
                'success_rate_pct' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
                'gross_volume' => round($volume, 2),
                'total_fees' => round($fees, 2),
                'net_volume' => round($net, 2),
            ],
            'rows' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function paymentMethodBreakdown(bool $isTestMode, ?int $merchantId, ?Carbon $from, ?Carbon $to): array
    {
        $rows = $this->transactionQuery($isTestMode, $merchantId, $from, $to)
            ->selectRaw('
                COALESCE(payment_method, \'unknown\') as payment_method,
                COUNT(*) as total_attempts,
                SUM(CASE WHEN status = \'success\' THEN 1 ELSE 0 END) as successful_count,
                COALESCE(SUM(CASE WHEN status = \'success\' THEN amount ELSE 0 END), 0) as gross_volume
            ')
            ->groupBy('payment_method')
            ->orderByDesc('gross_volume')
            ->get()
            ->map(function ($row) {
                $attempts = (int) $row->total_attempts;
                $success = (int) $row->successful_count;

                return [
                    'payment_method' => ucfirst((string) $row->payment_method),
                    'total_attempts' => $attempts,
                    'successful_count' => $success,
                    'failed_count' => $attempts - $success,
                    'success_rate_pct' => $attempts > 0 ? round(($success / $attempts) * 100, 2) : 0,
                    'gross_volume' => round((float) $row->gross_volume, 2),
                ];
            })
            ->values()
            ->all();

        return [
            'report_type' => self::REPORT_PAYMENT_METHOD,
            'summary' => [
                'methods_count' => count($rows),
                'gross_volume' => round(collect($rows)->sum('gross_volume'), 2),
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function successRateByMethod(bool $isTestMode, ?int $merchantId, ?Carbon $from, ?Carbon $to): array
    {
        $payload = $this->paymentMethodBreakdown($isTestMode, $merchantId, $from, $to);
        $payload['report_type'] = self::REPORT_SUCCESS_RATE;

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    protected function refundSummary(bool $isTestMode, ?int $merchantId, ?Carbon $from, ?Carbon $to): array
    {
        $query = Refund::query()
            ->whereHas('transaction', function ($q) use ($isTestMode, $merchantId) {
                $q->where('test_mode', $isTestMode);
                if ($merchantId) {
                    $q->where('merchant_id', $merchantId);
                }
            });

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        $total = (clone $query)->count();
        $amount = (float) (clone $query)->sum('amount');
        $completed = (clone $query)->whereIn('status', ['completed', 'success', 'processed'])->count();
        $pending = (clone $query)->whereIn('status', ['pending', 'initiated', 'processing'])->count();
        $failed = (clone $query)->whereIn('status', ['failed', 'rejected'])->count();

        $byStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(amount), 0) as amt')
            ->groupBy('status')
            ->orderByDesc('cnt')
            ->get()
            ->map(fn ($r) => [
                'status' => (string) $r->status,
                'count' => (int) $r->cnt,
                'amount' => round((float) $r->amt, 2),
            ])
            ->values()
            ->all();

        return [
            'report_type' => self::REPORT_REFUND_SUMMARY,
            'summary' => [
                'total_refunds' => $total,
                'refund_amount' => round($amount, 2),
                'completed_count' => $completed,
                'pending_count' => $pending,
                'failed_count' => $failed,
            ],
            'rows' => $byStatus,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function settlementSummary(bool $isTestMode, ?int $merchantId, ?Carbon $from, ?Carbon $to): array
    {
        $query = Settlement::query()->where('test_mode', $isTestMode);

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        $rows = (clone $query)
            ->selectRaw('
                COALESCE(status, settlement_status, \'unknown\') as settlement_status,
                COUNT(*) as settlement_count,
                COALESCE(SUM(net_amount), 0) as net_settled,
                COALESCE(SUM(fee_amount), 0) as fees,
                COALESCE(SUM(refund_amount), 0) as refunds
            ')
            ->groupBy(DB::raw('COALESCE(status, settlement_status, \'unknown\')'))
            ->orderByDesc('net_settled')
            ->get()
            ->map(fn ($r) => [
                'status' => (string) $r->settlement_status,
                'settlement_count' => (int) $r->settlement_count,
                'net_settled' => round((float) $r->net_settled, 2),
                'fees' => round((float) $r->fees, 2),
                'refunds' => round((float) $r->refunds, 2),
            ])
            ->values()
            ->all();

        return [
            'report_type' => self::REPORT_SETTLEMENT_SUMMARY,
            'summary' => [
                'total_settlements' => (clone $query)->count(),
                'net_settled' => round((float) (clone $query)->sum('net_amount'), 2),
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function dailyTrend(bool $isTestMode, ?int $merchantId, ?Carbon $from, ?Carbon $to): array
    {
        $rows = $this->transactionQuery($isTestMode, $merchantId, $from, $to)
            ->selectRaw('
                DATE(created_at) as report_date,
                COUNT(*) as total_attempts,
                SUM(CASE WHEN status = \'success\' THEN 1 ELSE 0 END) as successful_count,
                COALESCE(SUM(CASE WHEN status = \'success\' THEN amount ELSE 0 END), 0) as gross_volume
            ')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('report_date')
            ->get()
            ->map(function ($row) {
                $attempts = (int) $row->total_attempts;
                $success = (int) $row->successful_count;

                return [
                    'report_date' => (string) $row->report_date,
                    'total_attempts' => $attempts,
                    'successful_count' => $success,
                    'success_rate_pct' => $attempts > 0 ? round(($success / $attempts) * 100, 2) : 0,
                    'gross_volume' => round((float) $row->gross_volume, 2),
                ];
            })
            ->values()
            ->all();

        return [
            'report_type' => self::REPORT_DAILY_TREND,
            'summary' => [
                'days' => count($rows),
                'gross_volume' => round(collect($rows)->sum('gross_volume'), 2),
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return Builder<Transaction>
     */
    protected function transactionQuery(bool $isTestMode, ?int $merchantId, ?Carbon $from, ?Carbon $to): Builder
    {
        $query = Transaction::query()->where('test_mode', $isTestMode);

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function toCsv(string $reportType, array $payload): string
    {
        $out = fopen('php://temp', 'r+');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $summary = $payload['summary'] ?? [];
        fputcsv($out, ['Report', PaymentAnalyticsReportService::reportLabels()[$reportType] ?? $reportType]);
        foreach ($summary as $key => $value) {
            fputcsv($out, [ucwords(str_replace('_', ' ', (string) $key)), $value]);
        }
        fputcsv($out, []);

        $rows = $payload['rows'] ?? [];
        if ($rows !== []) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, array_values($row));
            }
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv ?: '';
    }
}
