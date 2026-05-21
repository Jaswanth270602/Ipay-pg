<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\PaymentAnalyticsReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PaymentAnalyticsController extends Controller
{
    public function __construct(
        protected PaymentAnalyticsReportService $reports
    ) {}

    public function index(): View
    {
        return view('merchant.reports.analytics', [
            'reportTypes' => PaymentAnalyticsReportService::reportLabels(),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant;
        if (! $merchant) {
            return response()->json(['success' => false, 'message' => 'Merchant not found'], 403);
        }

        $reportType = (string) $request->get('report_type', PaymentAnalyticsReportService::REPORT_TRANSACTION_SUMMARY);
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if ($fromDate && $toDate && strtotime($fromDate) > strtotime($toDate)) {
            return response()->json(['success' => false, 'message' => 'From date must be before or equal to To date'], 400);
        }

        try {
            $payload = $this->reports->build(
                $reportType,
                (bool) $merchant->test_mode,
                (int) $merchant->id,
                $fromDate,
                $toDate
            );

            return response()->json([
                'success' => true,
                'data' => $payload,
                'meta' => [
                    'report_type' => $reportType,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'environment' => $merchant->test_mode ? 'TEST' : 'LIVE',
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to generate report: ' . $e->getMessage()], 500);
        }
    }

    public function export(Request $request): Response
    {
        $merchant = $request->user()->merchant;
        if (! $merchant) {
            abort(403);
        }

        $reportType = (string) $request->get('report_type', PaymentAnalyticsReportService::REPORT_TRANSACTION_SUMMARY);
        try {
            $payload = $this->reports->build(
                $reportType,
                (bool) $merchant->test_mode,
                (int) $merchant->id,
                $request->get('from_date'),
                $request->get('to_date')
            );
        } catch (\Throwable $e) {
            abort(400, 'Invalid date range or report: ' . $e->getMessage());
        }

        $label = str_replace(' ', '_', PaymentAnalyticsReportService::reportLabels()[$reportType] ?? $reportType);
        $filename = 'merchant_' . $label . '_' . now()->format('Y-m-d') . '.csv';

        return response($this->reports->toCsv($reportType, $payload))
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
