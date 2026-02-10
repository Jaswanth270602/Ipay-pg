<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionsController extends Controller
{
    public function index(): View
    {
        return view('admin.subscriptions.index');
    }

    // Plans
    public function getPlans(Request $request)
    {
        $q = Plan::query();
        if ($request->status) $q->where('status', $request->status);
        if ($request->search) $q->where('name', 'like', '%'.$request->search.'%');
        return response()->json(['success' => true, 'data' => $q->orderByDesc('created_at')->paginate(10)]);
    }

    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:64|unique:plans,code',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'interval' => 'required|in:day,week,month,year',
            'interval_count' => 'required|integer|min:1',
            'trial_days' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);
        $plan = Plan::create($validated);
        return response()->json(['success' => true, 'data' => $plan]);
    }

    public function updatePlan(Request $request, int $id)
    {
        $plan = Plan::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:120',
            'code' => 'sometimes|string|max:64|unique:plans,code,'.$plan->id,
            'amount' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'interval' => 'sometimes|in:day,week,month,year',
            'interval_count' => 'sometimes|integer|min:1',
            'trial_days' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:active,inactive',
        ]);
        $plan->fill($validated)->save();
        return response()->json(['success' => true, 'data' => $plan]);
    }

    // Subscriptions
    public function getSubscriptions(Request $request)
    {
        $q = Subscription::with(['plan','merchant']);
        if ($request->status) $q->where('status', $request->status);
        if ($request->merchant_id) $q->where('merchant_id', $request->merchant_id);
        if ($request->plan_id) $q->where('plan_id', $request->plan_id);
        return response()->json(['success' => true, 'data' => $q->orderByDesc('created_at')->paginate(10)]);
    }

    public function createSubscription(Request $request)
    {
        $validated = $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
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

        $merchant = Merchant::findOrFail($validated['merchant_id']);
        
        $subscription = Subscription::create([
            'merchant_id' => $validated['merchant_id'],
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => $start,
            'current_period_end' => $end,
            'cancel_at_period_end' => false,
            'test_mode' => (bool)($merchant->test_mode ?? true),
            'metadata' => [
                'created_via' => 'admin_portal',
            ],
        ]);

        return response()->json(['success' => true, 'data' => $subscription->load(['plan','merchant'])]);
    }

    public function updateSubscription(Request $request, int $id)
    {
        $subscription = Subscription::findOrFail($id);
        $validated = $request->validate([
            'status' => 'sometimes|in:active,past_due,canceled,expired',
            'cancel_at_period_end' => 'sometimes|boolean',
        ]);
        $subscription->fill($validated)->save();
        return response()->json(['success' => true, 'data' => $subscription]);
    }
}


