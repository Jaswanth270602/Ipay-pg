<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SubscriptionsController extends Controller
{
    public function index(): View
    {
        return view('merchant.subscriptions.index');
    }

    public function getPlans(Request $request): JsonResponse
    {
        $q = Plan::query()->where('status', 'active');
        if ($request->search) {
            $q->where('name', 'like', '%'.$request->search.'%');
        }
        return response()->json([
            'success' => true,
            'data' => $q->orderByDesc('created_at')->paginate(10),
        ]);
    }

    public function getSubscriptions(Request $request): JsonResponse
    {
        $merchantId = $request->user()->merchant->id;
        $q = Subscription::with(['plan'])
            ->where('merchant_id', $merchantId)
            ->orderByDesc('created_at');

        if ($request->status) {
            $q->where('status', $request->status);
        }

        return response()->json([
            'success' => true,
            'data' => $q->paginate(10),
        ]);
    }

    public function createSubscription(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant;

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        $start = now();
        $end = match ($plan->interval) {
            'day' => now()->addDays($plan->interval_count),
            'week' => now()->addWeeks($plan->interval_count),
            'month' => now()->addMonths($plan->interval_count),
            'year' => now()->addYears($plan->interval_count),
            default => now()->addMonths(1),
        };

        $subscription = Subscription::create([
            'merchant_id' => $merchant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => $start,
            'current_period_end' => $end,
            'cancel_at_period_end' => false,
            'test_mode' => (bool)($merchant->test_mode ?? true),
            'metadata' => [
                'created_via' => 'merchant_portal',
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => $subscription->load(['plan']),
        ]);
    }

    public function updateSubscription(Request $request, int $id): JsonResponse
    {
        $merchantId = $request->user()->merchant->id;
        $subscription = Subscription::where('merchant_id', $merchantId)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:active,past_due,canceled,expired',
            'cancel_at_period_end' => 'sometimes|boolean',
        ]);

        $subscription->fill($validated)->save();

        return response()->json([
            'success' => true,
            'data' => $subscription->fresh('plan'),
        ]);
    }
}


