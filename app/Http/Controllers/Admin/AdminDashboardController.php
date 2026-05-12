<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\Refund;
use App\Models\Dispute;
use App\Services\DashboardDisplayCurrencyService;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    use LogsConditionally;

    public function __construct(
        private readonly DashboardDisplayCurrencyService $displayCurrency
    ) {}

    public function index(): View
    {
        $this->logInfo('Admin dashboard accessed', ['user_id' => auth()->id()]);
        return view('admin.dashboard');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $adminViewMode = session('admin_view_mode', 'test');
            $isTestMode = $adminViewMode === 'test';

            $this->logInfo('Admin dashboard data requested', [
                'user_id' => auth()->id(),
                'admin_view_mode' => $adminViewMode,
            ]);

            $startDate = $request->get('start_date', Carbon::now()->subDays(10)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            $startDateTime = Carbon::parse($startDate)->startOfDay();
            $endDateTime = Carbon::parse($endDate)->endOfDay();

            $daysDiff = $startDateTime->diffInDays($endDateTime) + 1;

            $usdRates = $this->displayCurrency->getUsdBasedRates();
            $displayCode = $this->displayCurrency->displayCurrencyCode();

            $totalGTV = $this->displayCurrency->sumTransactionAmountsToDisplayCurrency(
                Transaction::query()
                    ->where('status', 'success')
                    ->where('test_mode', $isTestMode)
                    ->whereBetween('created_at', [$startDateTime, $endDateTime]),
                $usdRates
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

            $amountRefunded = $this->displayCurrency->sumRefundAmountsToDisplayCurrency($refundForSum, $usdRates);

            $disputeForSum = Dispute::query()
                ->join('transactions', 'disputes.transaction_id', '=', 'transactions.id')
                ->where('transactions.test_mode', $isTestMode)
                ->whereBetween('disputes.created_at', [$startDateTime, $endDateTime]);

            $chargebackAmount = $this->displayCurrency->sumDisputeAmountsToDisplayCurrency($disputeForSum, $usdRates);

            $gtvRows = $this->displayCurrency->successfulTxnVolumeRowsByDayAndCurrency(
                Transaction::query()
                    ->where('status', 'success')
                    ->where('test_mode', $isTestMode)
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
            );

            $gtvByDate = $gtvRows->groupBy(function ($r) {
                $d = $r->agg_date;
                if ($d instanceof \DateTimeInterface) {
                    return $d->format('Y-m-d');
                }

                return substr((string) $d, 0, 10);
            })->map(fn ($group) => $this->displayCurrency->sumConvertedCurrencyGroups($group, 'agg_total', 'agg_currency', $usdRates));

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

                $gtvChartData[] = [
                    'date' => $dayKey,
                    'value' => $dayGTV,
                ];

                $transactionCountChartData[] = [
                    'date' => $dayKey,
                    'value' => $dayCount,
                ];

                $currentDate->addDay();
            }

            $pmRows = $this->displayCurrency->successfulTxnVolumeRowsByPaymentMethodAndCurrency(
                Transaction::where('status', 'success')
                    ->where('test_mode', $isTestMode)
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
            );

            $paymentModeDistribution = $pmRows->groupBy(fn ($r) => $r->payment_method ?: 'Unknown')
                ->map(function ($group) use ($usdRates) {
                    $mode = $group->first()->payment_method ?: 'Unknown';
                    $amount = $this->displayCurrency->sumConvertedCurrencyGroups($group, 'agg_total', 'agg_currency', $usdRates);
                    $count = (int) $group->sum(fn ($row) => (int) $row->cnt);

                    return [
                        'mode' => $mode,
                        'count' => $count,
                        'amount' => $amount,
                    ];
                })
                ->values();

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
                ->map(fn ($group) => $group->count())
                ->map(function ($count, $device) {
                    return [
                        'device' => $device,
                        'count' => $count,
                    ];
                })
                ->values();

            if ($deviceDistribution->isEmpty()) {
                $deviceDistribution = collect([
                    ['device' => 'Desktop', 'count' => 0],
                    ['device' => 'Mobile', 'count' => 0],
                    ['device' => 'Tablet', 'count' => 0],
                ]);
            }

            $stats = [
                'total_merchants' => Merchant::count(),
                'active_merchants' => Merchant::where('status', 'active')->count(),
                'total_transactions' => Transaction::where('test_mode', $isTestMode)->count(),
                'total_volume' => $this->displayCurrency->sumTransactionAmountsToDisplayCurrency(
                    Transaction::query()
                        ->where('status', 'success')
                        ->where('test_mode', $isTestMode),
                    $usdRates
                ),
                'total_gtv' => $totalGTV,
                'successful_transactions' => $successfulTransactions,
                'amount_refunded' => $amountRefunded,
                'chargeback_amount' => $chargebackAmount,
                'days_label' => "Last {$daysDiff} days",
                'display_currency' => $displayCode,
            ];

            $this->logInfo('Admin dashboard data retrieved successfully', [
                'stats' => $stats,
                'date_range' => [$startDate, $endDate],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
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
                ],
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
}
