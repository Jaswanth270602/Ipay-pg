<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentLink;
use App\Models\Refund;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private function buildNotifications($vendor): array
    {
        $pendingLinks = PaymentLink::where('vendor_id', $vendor->id)
            ->whereIn('status', ['active', 'created'])
            ->count();

        $failedTx = Transaction::where('status', 'failed')
            ->whereHas('order.paymentLink', function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            })->count();

        $pendingRefunds = Refund::whereIn('status', ['pending', 'pending_approval', 'pending_processing', 'processing'])
            ->whereHas('transaction.order.paymentLink', function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            })->count();

        $items = [];
        if ($pendingLinks > 0) {
            $items[] = ['type' => 'info', 'text' => "{$pendingLinks} payment link(s) pending payment."];
        }
        if ($failedTx > 0) {
            $items[] = ['type' => 'danger', 'text' => "{$failedTx} failed transaction attempt(s) detected."];
        }
        if ($pendingRefunds > 0) {
            $items[] = ['type' => 'warning', 'text' => "{$pendingRefunds} refund request(s) still processing."];
        }
        if (empty($items)) {
            $items[] = ['type' => 'success', 'text' => 'No pending alerts. All clear.'];
        }

        return $items;
    }

    private function decoratePaymentLinkStatus($paymentLinks)
    {
        return $paymentLinks->map(function ($link) {
            $failedCount = Transaction::whereHas('order', function ($q) use ($link) {
                $q->where('payment_link_id', $link->id);
            })->where('status', 'failed')->count();

            $successCount = Transaction::whereHas('order', function ($q) use ($link) {
                $q->where('payment_link_id', $link->id);
            })->where('status', 'success')->count();

            $displayStatus = 'pending';
            if ($link->status === 'paid' || $successCount > 0) {
                $displayStatus = 'paid';
            } elseif ($failedCount > 0 && $successCount === 0) {
                $displayStatus = 'failed';
            }

            $link->display_status = $displayStatus;
            return $link;
        });
    }

    private function metrics($vendor): array
    {
        $ordersCount = Order::whereHas('paymentLink', function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        })->count();

        $transactionsCount = Transaction::where('status', 'success')
            ->whereHas('order.paymentLink', function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            })
            ->count();

        $collectedAmount = (float) Transaction::where('status', 'success')
            ->whereHas('order.paymentLink', function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            })
            ->sum('amount');

        $settledAmount = (float) Transaction::where('status', 'success')
            ->where('settlement_status', 'settled')
            ->whereHas('order.paymentLink', function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            })
            ->sum('amount');

        $refundsCount = Refund::whereHas('transaction.order.paymentLink', function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        })->count();

        $refundAmount = (float) Refund::whereHas('transaction.order.paymentLink', function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        })->whereIn('status', ['completed', 'approved', 'processing', 'pending', 'pending_approval', 'pending_processing'])
            ->sum('amount');

        return [
            'ordersCount' => $ordersCount,
            'transactionsCount' => $transactionsCount,
            'collectedAmount' => round($collectedAmount, 2),
            'settledAmount' => round($settledAmount, 2),
            'balanceAmount' => round(max(0, $collectedAmount - $settledAmount), 2),
            'refundsCount' => $refundsCount,
            'refundAmount' => round($refundAmount, 2),
        ];
    }

    public function index(): View
    {
        $vendor = Auth::guard('vendor')->user();

        $paymentLinks = PaymentLink::query()
            ->where('vendor_id', $vendor->id)
            ->latest()
            ->limit(10)
            ->get(['id', 'title', 'amount', 'status', 'created_at']);
        $paymentLinks = $this->decoratePaymentLinkStatus($paymentLinks);

        return view('vendor.dashboard', array_merge([
            'vendor' => $vendor,
            'paymentLinks' => $paymentLinks,
            'paymentLinksCount' => PaymentLink::where('vendor_id', $vendor->id)->count(),
            'vendorNotifications' => $this->buildNotifications($vendor),
            'activeTab' => 'dashboard',
        ], $this->metrics($vendor)));
    }

    public function paymentLinks(): View
    {
        $vendor = Auth::guard('vendor')->user();
        $paymentLinks = PaymentLink::where('vendor_id', $vendor->id)
            ->latest()->paginate(20);
        $paymentLinks->setCollection(
            $this->decoratePaymentLinkStatus($paymentLinks->getCollection())
        );

        return view('vendor.payment-links', array_merge([
            'vendor' => $vendor,
            'paymentLinks' => $paymentLinks,
            'paymentLinksCount' => PaymentLink::where('vendor_id', $vendor->id)->count(),
            'vendorNotifications' => $this->buildNotifications($vendor),
            'activeTab' => 'payment-links',
        ], $this->metrics($vendor)));
    }

    public function orders(): View
    {
        $vendor = Auth::guard('vendor')->user();
        $orders = Order::whereHas('paymentLink', function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        })->latest()->paginate(20);

        $linksWithoutOrders = PaymentLink::where('vendor_id', $vendor->id)
            ->whereNotIn('id', function ($q) {
                $q->select('payment_link_id')
                    ->from('orders')
                    ->whereNotNull('payment_link_id');
            })
            ->latest()
            ->limit(20)
            ->get(['id', 'title', 'amount', 'status', 'created_at']);

        return view('vendor.orders', array_merge([
            'vendor' => $vendor,
            'orders' => $orders,
            'linksWithoutOrders' => $linksWithoutOrders,
            'paymentLinksCount' => PaymentLink::where('vendor_id', $vendor->id)->count(),
            'vendorNotifications' => $this->buildNotifications($vendor),
            'activeTab' => 'orders',
        ], $this->metrics($vendor)));
    }

    public function refunds(): View
    {
        $vendor = Auth::guard('vendor')->user();
        $refunds = Refund::whereHas('transaction.order.paymentLink', function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        })->latest()->paginate(20);

        return view('vendor.refunds', array_merge([
            'vendor' => $vendor,
            'refunds' => $refunds,
            'paymentLinksCount' => PaymentLink::where('vendor_id', $vendor->id)->count(),
            'vendorNotifications' => $this->buildNotifications($vendor),
            'activeTab' => 'refunds',
        ], $this->metrics($vendor)));
    }

    public function settlements(): View
    {
        $vendor = Auth::guard('vendor')->user();
        $transactions = Transaction::where('status', 'success')
            ->whereHas('order.paymentLink', function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            })
            ->whereNotNull('settlement_status')
            ->latest()
            ->paginate(20);

        return view('vendor.settlements', array_merge([
            'vendor' => $vendor,
            'transactions' => $transactions,
            'paymentLinksCount' => PaymentLink::where('vendor_id', $vendor->id)->count(),
            'vendorNotifications' => $this->buildNotifications($vendor),
            'activeTab' => 'settlements',
        ], $this->metrics($vendor)));
    }
}

