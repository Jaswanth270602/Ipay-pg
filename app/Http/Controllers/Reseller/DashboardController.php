<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Refund;
use App\Models\Reseller;
use App\Models\Transaction;
use App\Services\DashboardDisplayCurrencyService;
use App\Services\DashboardMetricsCacheService;
use App\Services\FileLifecycleService;
use App\Support\DashboardFxContext;
use App\Support\PaymentViewMode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardDisplayCurrencyService $displayCurrency,
        private readonly DashboardMetricsCacheService $metricsCache,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $reseller = $user->reseller;
        $fxCtx = DashboardFxContext::fromRequest($request);

        $stats = $reseller
            ? $this->metricsCache->remember('reseller_summary', [
                'reseller_id' => $reseller->id,
                'test' => PaymentViewMode::cacheTestFlag(),
                'merchant_id' => 0,
                'from' => '',
                'to' => '',
                'currency' => $fxCtx->displayCurrency,
                'fx_mode' => $fxCtx->mode,
            ], fn () => $this->buildDashboardStats($reseller->id, null, null, null, $fxCtx))
            : $this->buildDashboardStats(null, null, null, null, $fxCtx);

        if ($reseller) {
            $merchantOptions = $reseller->assignedMerchants()->get(['id', 'name']);
        } else {
            $merchantOptions = collect();
        }

        return view('reseller.dashboard', [
            'user' => $user,
            'reseller' => $reseller,
            'stats' => $stats,
            'merchantOptions' => $merchantOptions,
            'dashboard_display_currency' => $fxCtx->displayCurrency,
            'dashboard_fx_mode' => $fxCtx->mode,
            'fx_options' => $this->displayCurrency->fxOptionsPayload(),
        ]);
    }

    public function merchantSummary(Request $request): JsonResponse
    {
        $reseller = $request->user()->reseller;
        if (! $reseller) {
            return response()->json(['success' => false, 'message' => 'Reseller not found'], 403);
        }

        try {
            [$from, $to] = $this->resolveDateRange($request);
            $fxCtx = DashboardFxContext::fromRequest($request);
            $search = trim((string) $request->get('search', ''));
            $merchantId = (int) $request->get('merchant_id', 0);
            $perPage = min(max((int) $request->get('per_page', 10), 1), 100);
            $page = max(1, (int) $request->get('page', 1));

            $fromKey = $from?->format('Y-m-d') ?? '';
            $toKey = $to?->format('Y-m-d') ?? '';

            if ($merchantId > 0 && ! $reseller->assignedMerchantIds()->contains($merchantId)) {
                return response()->json(['success' => false, 'message' => 'Invalid merchant'], 422);
            }

            $cached = $this->metricsCache->remember('reseller_merchant_summary', [
                'reseller_id' => $reseller->id,
                'test' => PaymentViewMode::cacheTestFlag(),
                'merchant_id' => $merchantId,
                'from' => $fromKey,
                'to' => $toKey,
                'currency' => $fxCtx->displayCurrency,
                'fx_mode' => $fxCtx->mode,
                'page' => $page,
                'per_page' => $perPage,
                'search' => $search,
            ], function () use ($reseller, $request, $from, $to, $fxCtx, $search, $merchantId, $perPage, $page) {
                return $this->buildMerchantSummaryPayload($reseller, $request, $from, $to, $fxCtx, $search, $merchantId, $perPage, $page);
            });

            return response()->json(array_merge(['success' => true, 'fx_options' => $this->displayCurrency->fxOptionsPayload()], $cached));
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load merchant performance',
            ], 500);
        }
    }

    /**
     * @return array{summary: array, data: array, pagination: array}
     */
    protected function buildMerchantSummaryPayload(
        $reseller,
        Request $request,
        ?Carbon $from,
        ?Carbon $to,
        DashboardFxContext $fxCtx,
        string $search,
        int $merchantId,
        int $perPage,
        int $page
    ): array {
        $usdRates = $this->displayCurrency->getUsdBasedRates();
        $isTestMode = PaymentViewMode::isTestMode();

        $merchantIds = $reseller->assignedMerchantIds();
        if ($merchantId > 0) {
            $merchantIds = collect([$merchantId]);
        }
        if ($merchantIds->isEmpty()) {
            return [
                'summary' => $this->buildDashboardStats($reseller->id, $from, $to, $merchantId > 0 ? $merchantId : null, $fxCtx),
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                    'from' => null,
                    'to' => null,
                ],
            ];
        }

        $txAgg = DB::table('transactions')
            ->selectRaw('merchant_id, COUNT(*) as total_transactions, SUM(amount) as gross_volume')
            ->where('status', 'success')
            ->where('test_mode', $isTestMode)
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->groupBy('merchant_id');

        $refundAgg = DB::table('refunds')
            ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
            ->selectRaw('refunds.merchant_id, SUM(refunds.amount) as refund_amount')
            ->where('refunds.status', 'completed')
            ->where('transactions.test_mode', $isTestMode)
            ->when($from && $to, fn ($q) => $q->whereBetween('refunds.created_at', [$from, $to]))
            ->groupBy('refunds.merchant_id');

        $commissionAgg = DB::table('reseller_commissions')
            ->selectRaw("
                merchant_id,
                SUM(commission_amount) as gross_earnings,
                SUM(GREATEST(0, commission_amount - reversed_amount)) as commission_earned,
                SUM(CASE WHEN status IN ('pending','partially_reversed') THEN GREATEST(0, commission_amount - reversed_amount) ELSE 0 END) as pending_commission,
                SUM(CASE WHEN status = 'paid' THEN GREATEST(0, commission_amount - reversed_amount) ELSE 0 END) as paid_commission
            ")
            ->where('reseller_id', $reseller->id)
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->groupBy('merchant_id');

        $query = Merchant::query()
            ->whereIn('id', $merchantIds)
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
            ->leftJoinSub($txAgg, 'tx', fn ($j) => $j->on('merchants.id', '=', 'tx.merchant_id'))
            ->leftJoinSub($refundAgg, 'rf', fn ($j) => $j->on('merchants.id', '=', 'rf.merchant_id'))
            ->leftJoinSub($commissionAgg, 'cm', fn ($j) => $j->on('merchants.id', '=', 'cm.merchant_id'))
            ->selectRaw("
                merchants.id,
                merchants.name as merchant_name,
                COALESCE(tx.total_transactions, 0) as total_transactions,
                COALESCE(tx.gross_volume, 0) as gross_volume,
                COALESCE(rf.refund_amount, 0) as refund_amount,
                (COALESCE(tx.gross_volume, 0) - COALESCE(rf.refund_amount, 0)) as net_volume,
                COALESCE(cm.commission_earned, 0) as commission_earned,
                COALESCE(cm.pending_commission, 0) as pending_commission,
                COALESCE(cm.paid_commission, 0) as paid_commission
            ")
            ->orderBy('merchants.name');

        $rows = $query->paginate($perPage, ['*'], 'page', $page);
        $data = collect($rows->items())->map(function ($r) use ($from, $to, $fxCtx, $usdRates, $isTestMode) {
            $mid = (int) $r->id;
            $txQuery = Transaction::query()
                ->where('merchant_id', $mid)
                ->where('status', 'success')
                ->where('test_mode', $isTestMode)
                ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]));
            $grossVolume = $this->displayCurrency->sumTransactionAmountsToDisplayCurrency($txQuery, $usdRates, $fxCtx);

            $refundQuery = Refund::query()
                ->where('refunds.merchant_id', $mid)
                ->where('refunds.status', 'completed')
                ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
                ->where('transactions.test_mode', $isTestMode)
                ->when($from && $to, fn ($q) => $q->whereBetween('refunds.created_at', [$from, $to]));
            $refundAmount = $this->displayCurrency->sumRefundAmountsToDisplayCurrency($refundQuery, $usdRates, $fxCtx);

            return [
                'merchant_id' => $mid,
                'merchant_name' => (string) $r->merchant_name,
                'total_transactions' => (int) $r->total_transactions,
                'gross_volume' => $grossVolume,
                'refund_amount' => $refundAmount,
                'net_volume' => round(max(0, $grossVolume - $refundAmount), 2),
                'commission_earned' => round((float) $r->commission_earned, 2),
                'pending_commission' => round((float) $r->pending_commission, 2),
                'paid_commission' => round((float) $r->paid_commission, 2),
            ];
        });

        return [
            'summary' => $this->buildDashboardStats($reseller->id, $from, $to, $merchantId > 0 ? $merchantId : null, $fxCtx),
            'data' => $data->values()->all(),
            'pagination' => [
                'current_page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'last_page' => $rows->lastPage(),
                'from' => $rows->firstItem(),
                'to' => $rows->lastItem(),
            ],
        ];
    }

    public function merchantTransactionsView(Request $request): View
    {
        return view('reseller.merchant-transactions', [
            'merchantId' => (int) $request->get('merchant_id', 0),
            'merchantName' => trim((string) $request->get('merchant_name', '')),
        ]);
    }

    public function merchantTransactions(Request $request): JsonResponse
    {
        $reseller = $request->user()->reseller;
        if (! $reseller) {
            return response()->json(['success' => false, 'message' => 'Reseller not found'], 403);
        }

        $merchantId = (int) $request->get('merchant_id', 0);
        $allowedMerchantIds = $reseller->assignedMerchantIds();
        if ($merchantId <= 0 || ! $allowedMerchantIds->contains($merchantId)) {
            return response()->json(['success' => false, 'message' => 'Invalid merchant'], 422);
        }

        $perPage = min(max((int) $request->get('per_page', 10), 1), 100);
        $status = trim((string) $request->get('status', ''));
        [$from, $to] = $this->resolveDateRange($request);

        $isTestMode = PaymentViewMode::isTestMode();

        $query = Transaction::query()
            ->where('transactions.merchant_id', $merchantId)
            ->where('transactions.test_mode', $isTestMode)
            ->leftJoin('orders', 'transactions.order_id', '=', 'orders.id')
            ->leftJoin('reseller_commissions as rc', function ($join) use ($reseller) {
                $join->on('transactions.id', '=', 'rc.transaction_id')
                    ->where('rc.reseller_id', '=', $reseller->id);
            })
            ->selectRaw("
                transactions.id,
                transactions.txn_id,
                orders.order_id as order_ref,
                transactions.amount,
                transactions.status,
                transactions.created_at,
                COALESCE(GREATEST(0, rc.commission_amount - rc.reversed_amount), 0) as commission
            ")
            ->when($status !== '' && $status !== 'all', fn ($q) => $q->where('transactions.status', $status))
            ->when($from && $to, fn ($q) => $q->whereBetween('transactions.created_at', [$from, $to]))
            ->orderByDesc('transactions.created_at');

        $rows = $query->paginate($perPage);
        $data = collect($rows->items())->map(function ($r) {
            return [
                'transaction_id' => $r->txn_id,
                'order_id' => $r->order_ref ?: '-',
                'amount' => round((float) $r->amount, 2),
                'status' => $r->status,
                'commission' => round((float) $r->commission, 2),
                'created_at' => $r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d H:i:s') : '-',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->values()->all(),
            'pagination' => [
                'current_page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'last_page' => $rows->lastPage(),
                'from' => $rows->firstItem(),
                'to' => $rows->lastItem(),
            ],
        ]);
    }

    public function downloadReport(Request $request, FileLifecycleService $fileLifecycleService): BinaryFileResponse|JsonResponse
    {
        $reseller = $request->user()->reseller;
        if (! $reseller) {
            return response()->json(['success' => false, 'message' => 'Reseller not found'], 403);
        }

        $format = strtolower((string) $request->get('format', 'csv'));
        if (! in_array($format, ['csv', 'xlsx'], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid format'], 422);
        }

        [$from, $to] = $this->resolveDateRange($request);
        $merchantId = (int) $request->get('merchant_id', 0);
        $allowedMerchantIds = $reseller->assignedMerchantIds();
        if ($allowedMerchantIds->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No merchants assigned'], 422);
        }
        if ($merchantId > 0 && ! $allowedMerchantIds->contains($merchantId)) {
            return response()->json(['success' => false, 'message' => 'Invalid merchant'], 422);
        }

        $isTestMode = PaymentViewMode::isTestMode();

        $refundByTxn = DB::table('refunds')
            ->join('transactions as rt', 'refunds.transaction_id', '=', 'rt.id')
            ->selectRaw('refunds.transaction_id, SUM(refunds.amount) as refund_amount')
            ->where('refunds.status', 'completed')
            ->where('rt.test_mode', $isTestMode)
            ->groupBy('refunds.transaction_id');

        $commissionByTxn = DB::table('reseller_commissions')
            ->join('transactions as ct', 'reseller_commissions.transaction_id', '=', 'ct.id')
            ->selectRaw('reseller_commissions.transaction_id, SUM(GREATEST(0, reseller_commissions.commission_amount - reseller_commissions.reversed_amount)) as commission')
            ->where('reseller_commissions.reseller_id', $reseller->id)
            ->where('ct.test_mode', $isTestMode)
            ->groupBy('reseller_commissions.transaction_id');

        $rows = DB::table('transactions')
            ->leftJoin('merchants', 'transactions.merchant_id', '=', 'merchants.id')
            ->leftJoin('orders', 'transactions.order_id', '=', 'orders.id')
            ->leftJoinSub($refundByTxn, 'rf', fn ($j) => $j->on('transactions.id', '=', 'rf.transaction_id'))
            ->leftJoinSub($commissionByTxn, 'cm', fn ($j) => $j->on('transactions.id', '=', 'cm.transaction_id'))
            ->whereIn('transactions.merchant_id', $allowedMerchantIds)
            ->where('transactions.test_mode', $isTestMode)
            ->when($merchantId > 0, fn ($q) => $q->where('transactions.merchant_id', $merchantId))
            ->when($from && $to, fn ($q) => $q->whereBetween('transactions.created_at', [$from, $to]))
            ->orderByDesc('transactions.created_at')
            ->get([
                'merchants.name as merchant_name',
                'transactions.txn_id',
                'orders.order_id as order_ref',
                'transactions.amount',
                'transactions.status',
                'transactions.created_at',
                DB::raw('COALESCE(transactions.amount, 0) as gross_amount'),
                DB::raw('COALESCE(rf.refund_amount, 0) as refund_amount'),
                DB::raw('(COALESCE(transactions.amount, 0) - COALESCE(rf.refund_amount, 0)) as net_amount'),
                DB::raw('COALESCE(cm.commission, 0) as commission'),
            ]);

        $ext = $format === 'xlsx' ? 'xlsx' : 'csv';
        $filename = 'reseller_report_' . now()->format('Ymd_His') . '.' . $ext;

        // Keep implementation lightweight: CSV writer for both formats.
        $relativePath = $fileLifecycleService->createCsvReport($filename, function ($file) use ($rows): void {
            fputcsv($file, [
                'Merchant Name',
                'Transaction ID',
                'Order ID',
                'Amount',
                'Status',
                'Gross Amount',
                'Refund Amount',
                'Net Amount',
                'Commission',
                'Created At',
            ]);
            foreach ($rows as $r) {
                fputcsv($file, [
                    $r->merchant_name ?? '-',
                    $r->txn_id ?? '-',
                    $r->order_ref ?? '-',
                    number_format((float) $r->amount, 2, '.', ''),
                    $r->status ?? '-',
                    number_format((float) $r->gross_amount, 2, '.', ''),
                    number_format((float) $r->refund_amount, 2, '.', ''),
                    number_format((float) $r->net_amount, 2, '.', ''),
                    number_format((float) $r->commission, 2, '.', ''),
                    $r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d H:i:s') : '-',
                ]);
            }
        });

        $headers = $format === 'xlsx'
            ? ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            : ['Content-Type' => 'text/csv'];

        return $fileLifecycleService->downloadAndDelete($relativePath, $filename, $headers);
    }

    protected function buildDashboardStats(
        ?int $resellerId,
        ?Carbon $from = null,
        ?Carbon $to = null,
        ?int $merchantId = null,
        ?DashboardFxContext $fxCtx = null
    ): array {
        $fxCtx ??= new DashboardFxContext($this->displayCurrency->displayCurrencyCode());
        $usdRates = $this->displayCurrency->getUsdBasedRates();

        $stats = [
            'total_merchants' => 0,
            'total_transactions' => 0,
            'gross_volume' => 0.0,
            'net_volume' => 0.0,
            'total_refunds' => 0.0,
            'gross_earnings' => 0.0,
            'net_earnings' => 0.0,
            'pending_earnings' => 0.0,
            'paid_earnings' => 0.0,
            'display_currency' => $fxCtx->displayCurrency,
            'fx_mode' => $fxCtx->mode,
        ];
        if (! $resellerId) {
            return $stats;
        }

        $reseller = Reseller::query()->find($resellerId);
        $merchantIds = $reseller ? $reseller->assignedMerchantIds() : collect();
        if ($merchantId) {
            $merchantIds = $merchantIds->contains($merchantId) ? collect([$merchantId]) : collect();
        }
        if ($merchantIds->isEmpty()) {
            return $stats;
        }

        $stats['total_merchants'] = (int) $merchantIds->count();

        $isTestMode = PaymentViewMode::isTestMode();

        $successTx = Transaction::query()
            ->whereIn('merchant_id', $merchantIds)
            ->where('status', 'success')
            ->where('test_mode', $isTestMode)
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]));
        $stats['total_transactions'] = (int) (clone $successTx)->count();
        $stats['gross_volume'] = $this->displayCurrency->sumTransactionAmountsToDisplayCurrency(clone $successTx, $usdRates, $fxCtx);

        $refunds = Refund::query()
            ->whereIn('refunds.merchant_id', $merchantIds)
            ->where('refunds.status', 'completed')
            ->join('transactions', 'refunds.transaction_id', '=', 'transactions.id')
            ->where('transactions.test_mode', $isTestMode)
            ->when($from && $to, fn ($q) => $q->whereBetween('refunds.created_at', [$from, $to]));
        $stats['total_refunds'] = $this->displayCurrency->sumRefundAmountsToDisplayCurrency(clone $refunds, $usdRates, $fxCtx);
        $stats['net_volume'] = round(max(0, $stats['gross_volume'] - $stats['total_refunds']), 2);

        $commissionBase = DB::table('reseller_commissions')
            ->join('transactions', 'reseller_commissions.transaction_id', '=', 'transactions.id')
            ->where('reseller_commissions.reseller_id', $resellerId)
            ->where('transactions.test_mode', $isTestMode)
            ->when($merchantId, fn ($q) => $q->where('reseller_commissions.merchant_id', $merchantId))
            ->when($from && $to, fn ($q) => $q->whereBetween('reseller_commissions.created_at', [$from, $to]));

        $gross = (float) (clone $commissionBase)->sum('reseller_commissions.commission_amount');
        $netExpr = 'SUM(GREATEST(0, reseller_commissions.commission_amount - reseller_commissions.reversed_amount))';
        $net = (float) ((clone $commissionBase)->selectRaw($netExpr.' as n')->value('n') ?? 0);
        $pending = (float) ((clone $commissionBase)
            ->whereIn('reseller_commissions.status', ['pending', 'partially_reversed'])
            ->selectRaw($netExpr.' as n')
            ->value('n') ?? 0);
        $paid = (float) ((clone $commissionBase)
            ->where('reseller_commissions.status', 'paid')
            ->selectRaw($netExpr.' as n')
            ->value('n') ?? 0);

        $stats['gross_earnings'] = round($gross, 2);
        $stats['net_earnings'] = round($net, 2);
        $stats['pending_earnings'] = round($pending, 2);
        $stats['paid_earnings'] = round($paid, 2);

        return $stats;
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    protected function resolveDateRange(Request $request): array
    {
        $from = null;
        $to = null;
        try {
            if ($request->filled('date_from')) {
                $from = Carbon::parse((string) $request->get('date_from'))->startOfDay();
            }
            if ($request->filled('date_to')) {
                $to = Carbon::parse((string) $request->get('date_to'))->endOfDay();
            }
            if ((! $from || ! $to) && $request->filled('date_range')) {
                $parts = explode(' - ', (string) $request->get('date_range'));
                if (count($parts) === 2) {
                    $from = Carbon::parse(trim($parts[0]))->startOfDay();
                    $to = Carbon::parse(trim($parts[1]))->endOfDay();
                }
            }
        } catch (\Throwable) {
            $from = null;
            $to = null;
        }

        return [$from, $to];
    }
}
