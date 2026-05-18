<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Merchant;
use App\Models\Refund;
use App\Models\Transaction;
use App\Services\DashboardDisplayCurrencyService;
use App\Services\DashboardMetricsCacheService;
use App\Support\DashboardFxContext;
use App\Support\PaymentViewMode;
use App\Traits\LogsConditionally;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    use LogsConditionally;

    public function __construct(
        private readonly DashboardDisplayCurrencyService $displayCurrency,
        private readonly DashboardMetricsCacheService $metricsCache,
    ) {}

    public function index(): View
    {
        $this->logInfo('Admin dashboard accessed', ['user_id' => auth()->id()]);

        return view('admin.dashboard');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $isTestMode = PaymentViewMode::isTestMode();
            $adminViewMode = $isTestMode ? 'test' : 'live';

            $this->logInfo('Admin dashboard data requested', [
                'user_id' => auth()->id(),
                'admin_view_mode' => $adminViewMode,
            ]);

            $startDate = $request->get('start_date', Carbon::now()->subDays(10)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            $fxCtx = DashboardFxContext::fromRequest($request);

            $payload = $this->metricsCache->remember('admin', [
                'test' => $isTestMode ? 1 : 0,
                'start' => $startDate,
                'end' => $endDate,
                'currency' => $fxCtx->displayCurrency,
                'fx_mode' => $fxCtx->mode,
            ], fn () => $this->buildAdminDashboardPayload($isTestMode, $startDate, $endDate, $fxCtx));

            return response()->json([
                'success' => true,
                'data' => $payload,
            ]);
        } catch (\Exception $e) {
            $this->logError('Error fetching admin dashboard data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
            ], 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAdminDashboardPayload(bool $isTestMode, string $startDate, string $endDate, DashboardFxContext $fxCtx): array
    {
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        $daysDiff = $startDateTime->diffInDays($endDateTime) + 1;

        $usdRates = $this->displayCurrency->getUsdBasedRates();
        $displayCode = $fxCtx->displayCurrency;

        $totalGTV = $this->displayCurrency->sumTransactionAmountsToDisplayCurrency(
            Transaction::query()
                ->where('status', 'success')
                ->where('test_mode', $isTestMode)
                ->whereBetween('created_at', [$startDateTime, $endDateTime]),
            $usdRates,
            $fxCtx
        );

        $successfulTransactions = Transaction::where('status', 'success')
            ->where('test_mode', $isTestMode)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->count();

        $refundForSum = Refund::query()
            ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
            ->where('refunds.status', 'completed')
            ->where('transactions.test_mode', $isTestMode)
            ->where(function ($q) use ($startDateTime, $endDateTime) {
                $q->whereBetween('refunds.processed_at', [$startDateTime, $endDateTime])
                    ->orWhere(function ($sub) use ($startDateTime, $endDateTime) {
                        $sub->whereNull('refunds.processed_at')
                            ->whereBetween('refunds.created_at', [$startDateTime, $endDateTime]);
                    });
            });

        $amountRefunded = $this->displayCurrency->sumRefundAmountsToDisplayCurrency($refundForSum, $usdRates, $fxCtx);

        $disputeForSum = Dispute::query()
            ->join('transactions', 'disputes.transaction_id', '=', 'transactions.id')
            ->where('transactions.test_mode', $isTestMode)
            ->whereBetween('disputes.created_at', [$startDateTime, $endDateTime]);

        $chargebackAmount = $this->displayCurrency->sumDisputeAmountsToDisplayCurrency($disputeForSum, $usdRates, $fxCtx);

        $gtvRows = $this->displayCurrency->successfulTxnVolumeRowsByDayAndCurrency(
            Transaction::query()
                ->where('status', 'success')
                ->where('test_mode', $isTestMode)
                ->whereBetween('created_at', [$startDateTime, $endDateTime]),
            $fxCtx
        );

        $gtvByDate = $gtvRows->groupBy(function ($r) {
            $d = $r->agg_date;
            if ($d instanceof \DateTimeInterface) {
                return $d->format('Y-m-d');
            }

            return substr((string) $d, 0, 10);
        })->map(fn ($group) => $this->displayCurrency->sumConvertedCurrencyGroups($group, 'agg_total', 'agg_currency', $usdRates, $fxCtx->displayCurrency));

        $gtvChartData = [];
        $transactionCountChartData = [];
        $currentDate = $startDateTime->copy();

        while ($currentDate->lte($endDateTime)) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();
            $dayKey = $currentDate->format('Y-m-d');
            $dayGTV = (float) ($gtvByDate->get($dayKey) ?? 0);

            $dayCount = Transaction::where('status', 'success')
                ->where('test_mode', $isTestMode)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();

            $gtvChartData[] = ['date' => $dayKey, 'value' => $dayGTV];
            $transactionCountChartData[] = ['date' => $dayKey, 'value' => $dayCount];
            $currentDate->addDay();
        }

        $pmRows = $this->displayCurrency->successfulTxnVolumeRowsByPaymentMethodAndCurrency(
            Transaction::where('status', 'success')
                ->where('test_mode', $isTestMode)
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
        );

        $paymentModeDistribution = $pmRows->groupBy(fn ($r) => $r->payment_method ?: 'Unknown')
            ->map(function ($group) use ($usdRates, $fxCtx) {
                $mode = $group->first()->payment_method ?: 'Unknown';

                return [
                    'mode' => $mode,
                    'count' => (int) $group->sum(fn ($row) => (int) $row->cnt),
                    'amount' => $this->displayCurrency->sumConvertedCurrencyGroups($group, 'agg_total', 'agg_currency', $usdRates, $fxCtx->displayCurrency),
                ];
            })
            ->values()
            ->all();

        $deviceDistribution = $this->buildDeviceDistribution($isTestMode, $startDateTime, $endDateTime);

        $stats = [
            'total_merchants' => Merchant::count(),
            'active_merchants' => Merchant::where('status', 'active')->count(),
            'total_transactions' => Transaction::where('test_mode', $isTestMode)->count(),
            'total_volume' => $this->displayCurrency->sumTransactionAmountsToDisplayCurrency(
                Transaction::query()
                    ->where('status', 'success')
                    ->where('test_mode', $isTestMode),
                $usdRates,
                $fxCtx
            ),
            'total_gtv' => $totalGTV,
            'successful_transactions' => $successfulTransactions,
            'amount_refunded' => $amountRefunded,
            'chargeback_amount' => $chargebackAmount,
            'days_label' => "Last {$daysDiff} days",
            'display_currency' => $displayCode,
            'fx_mode' => $fxCtx->mode,
        ];

        return [
            'fx_options' => $this->displayCurrency->fxOptionsPayload(),
            'stats' => $stats,
            'charts' => [
                'gtv_and_count' => [
                    'gtv' => $gtvChartData,
                    'count' => $transactionCountChartData,
                ],
                'payment_mode_distribution' => $paymentModeDistribution,
                'device_distribution' => $deviceDistribution,
            ],
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];
    }

    /**
     * @return list<array{device: string, count: int}>
     */
    private function buildDeviceDistribution(bool $isTestMode, Carbon $startDateTime, Carbon $endDateTime): array
    {
        $deviceDistribution = Transaction::where('status', 'success')
            ->where('test_mode', $isTestMode)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->whereNotNull('user_agent')
            ->get()
            ->map(function ($transaction) {
                $userAgent = strtolower($transaction->user_agent);
                if (strpos($userAgent, 'mobile') !== false || strpos($userAgent, 'android') !== false || strpos($userAgent, 'iphone') !== false) {
                    return 'Mobile';
                }
                if (strpos($userAgent, 'tablet') !== false || strpos($userAgent, 'ipad') !== false) {
                    return 'Tablet';
                }

                return 'Desktop';
            })
            ->groupBy(fn ($device) => $device)
            ->map(fn ($group, $device) => ['device' => $device, 'count' => $group->count()])
            ->values();

        if ($deviceDistribution->isEmpty()) {
            return [
                ['device' => 'Desktop', 'count' => 0],
                ['device' => 'Mobile', 'count' => 0],
                ['device' => 'Tablet', 'count' => 0],
            ];
        }

        return $deviceDistribution->all();
    }
}
