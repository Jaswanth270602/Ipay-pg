<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettlementsController extends Controller
{
    public function index(): View
    {
        return view('merchant.settlements.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant;
        $perPage = min($request->get('per_page', 10), 100);
        
        $status = $request->get('status');
        $search = $request->get('search');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Show settlements based on merchant's current mode
        // Test mode shows settlements for test transactions
        // Live mode shows settlements for live transactions
        $query = $merchant->settlements()->latest();
        
        // Note: Settlements themselves don't have test_mode flag
        // They're filtered by which transactions they contain
        // For now, show all settlements for the merchant

        if ($status && $status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('settlement_id', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($fromDate) {
            $query->whereDate('settlement_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('settlement_date', '<=', $toDate);
        }

        $settlements = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $settlements->items(),
            'pagination' => [
                'current_page' => $settlements->currentPage(),
                'per_page' => $settlements->perPage(),
                'total' => $settlements->total(),
                'last_page' => $settlements->lastPage(),
                'from' => $settlements->firstItem(),
                'to' => $settlements->lastItem(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $merchant = $request->user()->merchant;

        $query = $merchant->settlements();

        $status = $request->get('status');
        $search = $request->get('search');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if ($status && $status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('settlement_id', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($fromDate) {
            $query->whereDate('settlement_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('settlement_date', '<=', $toDate);
        }

        $settlements = $query->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="settlements_' . now()->format('Y-m-d_His') . '.csv"',
        ];

        $callback = function() use ($settlements) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Settlement ID', 'Amount', 'Fee Amount', 'Refund Amount', 'Net Amount',
                'Currency', 'Transaction Count', 'Refund Count', 'Status',
                'Period Start', 'Period End', 'Settlement Date', 'Processed At', 'UTR Number'
            ]);

            foreach ($settlements as $settlement) {
                fputcsv($file, [
                    $settlement->settlement_id,
                    number_format($settlement->amount, 2),
                    number_format($settlement->fee_amount ?? 0, 2),
                    number_format($settlement->refund_amount ?? 0, 2),
                    number_format($settlement->net_amount, 2),
                    $settlement->currency ?? 'INR',
                    $settlement->transaction_count ?? 0,
                    $settlement->refund_count ?? 0,
                    $settlement->status,
                    $settlement->period_start ? $settlement->period_start->format('Y-m-d H:i:s') : '-',
                    $settlement->period_end ? $settlement->period_end->format('Y-m-d H:i:s') : '-',
                    $settlement->settlement_date ? $settlement->settlement_date->format('Y-m-d') : '-',
                    $settlement->processed_at ? $settlement->processed_at->format('Y-m-d H:i:s') : '-',
                    $settlement->utr_number ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

