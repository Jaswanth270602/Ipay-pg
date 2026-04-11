<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Refund;
use App\Models\Transaction;
use App\Services\FileLifecycleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $reseller = $user->reseller;

        $stats = $this->buildDashboardStats($reseller?->id);

        if ($reseller) {
            $merchantOptions = $reseller->merchants()->orderBy('name')->get(['id', 'name']);
        } else {
            $merchantOptions = collect();
        }

        return view('reseller.dashboard', [
            'user' => $user,
            'reseller' => $reseller,
            'stats' => $stats,
            'merchantOptions' => $merchantOptions,
        ]);
    }

    public function merchantSummary(Request $request): JsonResponse
    {
        $reseller = $request->user()->reseller;
        if (! $reseller) {
            return response()->json(['success' => false, 'message' => 'Reseller not found'], 403);
        }

        [$from, $to] = $this->resolveDateRange($request);
        $search = trim((string) $request->get('search', ''));
        $merchantId = (int) $request->get('merchant_id', 0);
        $perPage = min(max((int) $request->get('per_page', 10), 1), 100);

        $merchantIds = $reseller->merchants()->pluck('id');
        if ($merchantId > 0) {
            if (! $merchantIds->contains($merchantId)) {
                return response()->json(['success' => false, 'message' => 'Invalid merchant'], 422);
            }
            $merchantIds = collect([$merchantId]);
        }
        if ($merchantIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'summary' => $this->buildDashboardStats($reseller->id, $from, $to, $merchantId > 0 ? $merchantId : null),
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                    'from' => null,
                    'to' => null,
                ],
            ]);
        }

        $txAgg = DB::table('transactions')
            ->selectRaw('merchant_id, COUNT(*) as total_transactions, SUM(amount) as gross_volume')
            ->where('status', 'success')
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->groupBy('merchant_id');

        $refundAgg = DB::table('refunds')
            ->selectRaw('merchant_id, SUM(amount) as refund_amount')
            ->where('status', 'completed')
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->groupBy('merchant_id');

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

        $rows = $query->paginate($perPage);
        $data = collect($rows->items())->map(function ($r) {
            return [
                'merchant_id' => (int) $r->id,
                'merchant_name' => (string) $r->merchant_name,
                'total_transactions' => (int) $r->total_transactions,
                'gross_volume' => round((float) $r->gross_volume, 2),
                'refund_amount' => round((float) $r->refund_amount, 2),
                'net_volume' => round((float) $r->net_volume, 2),
                'commission_earned' => round((float) $r->commission_earned, 2),
                'pending_commission' => round((float) $r->pending_commission, 2),
                'paid_commission' => round((float) $r->paid_commission, 2),
            ];
        });

        return response()->json([
            'success' => true,
            'summary' => $this->buildDashboardStats($reseller->id, $from, $to, $merchantId > 0 ? $merchantId : null),
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
        $allowedMerchantIds = $reseller->merchants()->pluck('id');
        if ($merchantId <= 0 || ! $allowedMerchantIds->contains($merchantId)) {
            return response()->json(['success' => false, 'message' => 'Invalid merchant'], 422);
        }

        $perPage = min(max((int) $request->get('per_page', 10), 1), 100);
        $status = trim((string) $request->get('status', ''));
        [$from, $to] = $this->resolveDateRange($request);

        $query = Transaction::query()
            ->where('transactions.merchant_id', $merchantId)
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
        $allowedMerchantIds = $reseller->merchants()->pluck('id');
        if ($allowedMerchantIds->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No merchants assigned'], 422);
        }
        if ($merchantId > 0 && ! $allowedMerchantIds->contains($merchantId)) {
            return response()->json(['success' => false, 'message' => 'Invalid merchant'], 422);
        }

        $refundByTxn = DB::table('refunds')
            ->selectRaw('transaction_id, SUM(amount) as refund_amount')
            ->where('status', 'completed')
            ->groupBy('transaction_id');

        $commissionByTxn = DB::table('reseller_commissions')
            ->selectRaw('transaction_id, SUM(GREATEST(0, commission_amount - reversed_amount)) as commission')
            ->where('reseller_id', $reseller->id)
            ->groupBy('transaction_id');

        $rows = DB::table('transactions')
            ->leftJoin('merchants', 'transactions.merchant_id', '=', 'merchants.id')
            ->leftJoin('orders', 'transactions.order_id', '=', 'orders.id')
            ->leftJoinSub($refundByTxn, 'rf', fn ($j) => $j->on('transactions.id', '=', 'rf.transaction_id'))
            ->leftJoinSub($commissionByTxn, 'cm', fn ($j) => $j->on('transactions.id', '=', 'cm.transaction_id'))
            ->whereIn('transactions.merchant_id', $allowedMerchantIds)
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

    protected function buildDashboardStats(?int $resellerId, ?Carbon $from = null, ?Carbon $to = null, ?int $merchantId = null): array
    {
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
        ];
        if (! $resellerId) {
            return $stats;
        }

        $merchantIds = DB::table('reseller_merchant')
            ->where('reseller_id', $resellerId)
            ->pluck('merchant_id');
        if ($merchantId) {
            $merchantIds = $merchantIds->contains($merchantId) ? collect([$merchantId]) : collect();
        }
        if ($merchantIds->isEmpty()) {
            return $stats;
        }

        $stats['total_merchants'] = (int) $merchantIds->count();

        $successTx = Transaction::query()
            ->whereIn('merchant_id', $merchantIds)
            ->where('status', 'success')
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]));
        $stats['total_transactions'] = (int) (clone $successTx)->count();
        $stats['gross_volume'] = round((float) (clone $successTx)->sum('amount'), 2);

        $refunds = Refund::query()
            ->whereIn('merchant_id', $merchantIds)
            ->where('status', 'completed')
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]));
        $stats['total_refunds'] = round((float) (clone $refunds)->sum('amount'), 2);
        $stats['net_volume'] = round(max(0, $stats['gross_volume'] - $stats['total_refunds']), 2);

        $gross = (float) DB::table('reseller_commissions')
            ->where('reseller_id', $resellerId)
            ->when($merchantId, fn ($q) => $q->where('merchant_id', $merchantId))
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->sum('commission_amount');
        $netExpr = 'SUM(GREATEST(0, commission_amount - reversed_amount))';
        $net = (float) (DB::table('reseller_commissions')
            ->where('reseller_id', $resellerId)
            ->when($merchantId, fn ($q) => $q->where('merchant_id', $merchantId))
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->selectRaw($netExpr . ' as n')
            ->value('n') ?? 0);
        $pending = (float) (DB::table('reseller_commissions')
            ->where('reseller_id', $resellerId)
            ->whereIn('status', ['pending', 'partially_reversed'])
            ->when($merchantId, fn ($q) => $q->where('merchant_id', $merchantId))
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->selectRaw($netExpr . ' as n')
            ->value('n') ?? 0);
        $paid = (float) (DB::table('reseller_commissions')
            ->where('reseller_id', $resellerId)
            ->where('status', 'paid')
            ->when($merchantId, fn ($q) => $q->where('merchant_id', $merchantId))
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->selectRaw($netExpr . ' as n')
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
