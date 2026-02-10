<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class MISReportController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin MIS report page accessed', ['user_id' => auth()->id()]);
        return view('admin.manage-settlements.mis-report');
    }

    public function download(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'merchant_id' => 'nullable|exists:merchants,id',
                'format' => 'nullable|in:csv,xlsx',
            ]);

            $query = DB::table('settlements')
                ->leftJoin('merchants', 'settlements.merchant_id', '=', 'merchants.id')
                ->select('settlements.*', 'merchants.name as merchant_name')
                ->whereBetween('settlements.settlement_date', [$request->start_date, $request->end_date]);

            if ($request->has('merchant_id') && $request->merchant_id) {
                $query->where('settlements.merchant_id', $request->merchant_id);
            }

            $settlements = $query->get();

            $format = $request->get('format', 'csv');
            $filename = 'mis_report_' . date('Y-m-d') . '.' . $format;

            if ($format === 'csv') {
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ];

                $callback = function() use ($settlements) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, [
                        'Settlement ID', 'Merchant ID', 'Merchant Name', 'Payout Amount', 
                        'Settlement Status', 'Settlement Date', 'Bank Reference', 
                        'Account Name', 'Account Number', 'IFSC Code', 'Bank Name', 'Bank Branch'
                    ]);

                    foreach ($settlements as $settlement) {
                        fputcsv($file, [
                            $settlement->settlement_id ?? '-',
                            $settlement->merchant_id ?? '-',
                            $settlement->merchant_name ?? '-',
                            $settlement->payout_amount ?? '0.00',
                            $settlement->settlement_status ?? '-',
                            $settlement->settlement_date ? date('Y-m-d', strtotime($settlement->settlement_date)) : '-',
                            $settlement->bank_reference ?? '-',
                            $settlement->account_name ?? '-',
                            $settlement->account_number ?? '-',
                            $settlement->ifsc_code ?? '-',
                            $settlement->bank_name ?? '-',
                            $settlement->bank_branch ?? '-',
                        ]);
                    }
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }

            return response()->json(['success' => false, 'message' => 'Format not supported'], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage(),
            ], 500);
        }
    }
}
