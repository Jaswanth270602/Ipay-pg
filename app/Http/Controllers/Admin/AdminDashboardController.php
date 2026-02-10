<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\Refund;
use App\Models\Dispute;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin dashboard accessed', ['user_id' => auth()->id()]);
        return view('admin.dashboard');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $this->logInfo('Admin dashboard data requested', ['user_id' => auth()->id()]);
            
            // Get date range (default to last 10 days)
            $startDate = $request->get('start_date', Carbon::now()->subDays(10)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            
            $startDateTime = Carbon::parse($startDate)->startOfDay();
            $endDateTime = Carbon::parse($endDate)->endOfDay();
            
            // Calculate days difference for "Last X days" label
            $daysDiff = $startDateTime->diffInDays($endDateTime) + 1;
            
            // Total GTV (Gross Transaction Value) - sum of successful transaction amounts
            $totalGTV = Transaction::where('status', 'success')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->sum('amount');
            
            // Successful Transactions count
            $successfulTransactions = Transaction::where('status', 'success')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->count();
            
            // Amount Refunded
            $amountRefunded = Refund::where('status', 'completed')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->sum('amount');
            
            // ChargeBack Amount (from Disputes)
            $chargebackAmount = Dispute::whereBetween('created_at', [$startDateTime, $endDateTime])
                ->sum('amount');
            
            // Chart Data: Gross Transaction Value and Transaction Count (Last 10 days)
            $gtvChartData = [];
            $transactionCountChartData = [];
            $currentDate = $startDateTime->copy();
            
            while ($currentDate->lte($endDateTime)) {
                $dayStart = $currentDate->copy()->startOfDay();
                $dayEnd = $currentDate->copy()->endOfDay();
                
                $dayGTV = Transaction::where('status', 'success')
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->sum('amount');
                
                $dayCount = Transaction::where('status', 'success')
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->count();
                
                $gtvChartData[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'value' => (float) $dayGTV
                ];
                
                $transactionCountChartData[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'value' => $dayCount
                ];
                
                $currentDate->addDay();
            }
            
            // Payment Mode Distribution
            $paymentModeDistribution = Transaction::where('status', 'success')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
                ->groupBy('payment_method')
                ->get()
                ->map(function($item) {
                    return [
                        'mode' => $item->payment_method ?: 'Unknown',
                        'count' => $item->count,
                        'amount' => (float) $item->total_amount
                    ];
                });
            
            // Device Distribution (from user_agent)
            $deviceDistribution = Transaction::where('status', 'success')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->whereNotNull('user_agent')
                ->get()
                ->map(function($transaction) {
                    $userAgent = strtolower($transaction->user_agent);
                    if (strpos($userAgent, 'mobile') !== false || strpos($userAgent, 'android') !== false || strpos($userAgent, 'iphone') !== false) {
                        return 'Mobile';
                    } elseif (strpos($userAgent, 'tablet') !== false || strpos($userAgent, 'ipad') !== false) {
                        return 'Tablet';
                    } else {
                        return 'Desktop';
                    }
                })
                ->groupBy(function($device) {
                    return $device;
                })
                ->map(function($group) {
                    return $group->count();
                })
                ->map(function($count, $device) {
                    return [
                        'device' => $device,
                        'count' => $count
                    ];
                })
                ->values();
            
            // If no device data, return empty array
            if ($deviceDistribution->isEmpty()) {
                $deviceDistribution = collect([
                    ['device' => 'Desktop', 'count' => 0],
                    ['device' => 'Mobile', 'count' => 0],
                    ['device' => 'Tablet', 'count' => 0]
                ]);
            }
            
            $stats = [
                'total_merchants' => Merchant::count(),
                'active_merchants' => Merchant::where('status', 'active')->count(),
                'total_transactions' => Transaction::count(),
                'total_volume' => Transaction::where('status', 'success')->sum('amount'),
                'total_gtv' => $totalGTV,
                'successful_transactions' => $successfulTransactions,
                'amount_refunded' => $amountRefunded,
                'chargeback_amount' => $chargebackAmount,
                'days_label' => "Last {$daysDiff} days",
            ];

            $this->logInfo('Admin dashboard data retrieved successfully', [
                'stats' => $stats,
                'date_range' => [$startDate, $endDate]
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'charts' => [
                        'gtv_and_count' => [
                            'gtv' => $gtvChartData,
                            'count' => $transactionCountChartData
                        ],
                        'payment_mode_distribution' => $paymentModeDistribution,
                        'device_distribution' => $deviceDistribution
                    ],
                    'date_range' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ]
                ],
            ]);
        } catch (\Exception $e) {
            $this->logError('Error fetching admin dashboard data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
            ], 500);
        }
    }
}
