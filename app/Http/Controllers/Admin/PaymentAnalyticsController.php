<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PaymentAnalyticsReportService;
use App\Support\PaymentViewMode;
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
        return view('admin.reports.analytics', [
            'reportTypes' => PaymentAnalyticsReportService::reportLabels(),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $reportType = (string) $request->get('report_type', PaymentAnalyticsReportService::REPORT_TRANSACTION_SUMMARY);
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $merchantId = $request->filled('merchant_id') ? (int) $request->get('merchant_id') : null;

        if ($fromDate && $toDate && strtotime($fromDate) > strtotime($toDate)) {
            return response()->json(['success' => false, 'message' => 'From date must be before or equal to To date'], 400);
        }

        try {
            $isTestMode = PaymentViewMode::isTestMode();
            $payload = $this->reports->build($reportType, $isTestMode, $merchantId, $fromDate, $toDate);

            return response()->json([
                'success' => true,
                'data' => $payload,
                'meta' => [
                    'report_type' => $reportType,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'merchant_id' => $merchantId,
                    'environment' => $isTestMode ? 'TEST' : 'LIVE',
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
        $reportType = (string) $request->get('report_type', PaymentAnalyticsReportService::REPORT_TRANSACTION_SUMMARY);
        $merchantId = $request->filled('merchant_id') ? (int) $request->get('merchant_id') : null;
        $isTestMode = PaymentViewMode::isTestMode();

        $payload = $this->reports->build(
            $reportType,
            $isTestMode,
            $merchantId,
            $request->get('from_date'),
            $request->get('to_date')
        );

        $label = str_replace(' ', '_', PaymentAnalyticsReportService::reportLabels()[$reportType] ?? $reportType);
        $filename = 'admin_' . $label . '_' . now()->format('Y-m-d') . '.csv';

        return response($this->reports->toCsv($reportType, $payload))
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
