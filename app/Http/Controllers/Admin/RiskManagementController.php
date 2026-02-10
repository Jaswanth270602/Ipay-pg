<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RiskRule;
use App\Models\RiskEvent;
use App\Models\FraudAlert;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiskManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.risk.index');
    }

    // Risk Rules
    public function getRules(Request $request)
    {
        $q = RiskRule::query();
        if ($request->status) $q->where('status', $request->status);
        if ($request->type) $q->where('type', $request->type);
        if ($request->search) $q->where('name', 'like', '%'.$request->search.'%');
        return response()->json(['success' => true, 'data' => $q->orderBy('priority', 'desc')->orderByDesc('created_at')->paginate(10)]);
    }

    public function storeRule(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:velocity,amount_limit,geo_block,merchant_block,ip_block',
            'rule_config' => 'required|array',
            'action' => 'required|in:block,alert,review',
            'status' => 'required|in:active,inactive',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);
        $validated['priority'] = $validated['priority'] ?? 0;
        $rule = RiskRule::create($validated);
        return response()->json(['success' => true, 'data' => $rule]);
    }

    public function updateRule(Request $request, int $id)
    {
        $rule = RiskRule::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:velocity,amount_limit,geo_block,merchant_block,ip_block',
            'rule_config' => 'sometimes|array',
            'action' => 'sometimes|in:block,alert,review',
            'status' => 'sometimes|in:active,inactive',
            'priority' => 'sometimes|integer|min:0|max:100',
        ]);
        $rule->fill($validated)->save();
        return response()->json(['success' => true, 'data' => $rule]);
    }

    public function deleteRule(int $id)
    {
        $rule = RiskRule::findOrFail($id);
        $rule->delete();
        return response()->json(['success' => true]);
    }

    // Risk Events
    public function getEvents(Request $request)
    {
        $q = RiskEvent::with(['rule', 'merchant', 'transaction']);
        if ($request->severity) $q->where('severity', $request->severity);
        if ($request->resolved !== null) $q->where('resolved', $request->resolved);
        if ($request->merchant_id) $q->where('merchant_id', $request->merchant_id);
        if ($request->rule_id) $q->where('rule_id', $request->rule_id);
        return response()->json(['success' => true, 'data' => $q->orderByDesc('created_at')->paginate(15)]);
    }

    public function resolveEvent(Request $request, int $id)
    {
        $event = RiskEvent::findOrFail($id);
        $validated = $request->validate([
            'resolved' => 'required|boolean',
        ]);
        $event->resolved = $validated['resolved'];
        $event->resolved_at = $validated['resolved'] ? now() : null;
        $event->resolved_by = $validated['resolved'] ? auth()->id() : null;
        $event->save();
        return response()->json(['success' => true, 'data' => $event]);
    }

    // Fraud Alerts
    public function getAlerts(Request $request)
    {
        $q = FraudAlert::with(['merchant', 'transaction']);
        if ($request->status) $q->where('status', $request->status);
        if ($request->severity) $q->where('severity', $request->severity);
        if ($request->alert_type) $q->where('alert_type', $request->alert_type);
        if ($request->merchant_id) $q->where('merchant_id', $request->merchant_id);
        return response()->json(['success' => true, 'data' => $q->orderByDesc('created_at')->paginate(15)]);
    }

    public function createAlert(Request $request)
    {
        $validated = $request->validate([
            'merchant_id' => 'nullable|exists:merchants,id',
            'transaction_id' => 'nullable|exists:transactions,id',
            'alert_type' => 'required|in:suspicious_pattern,chargeback_risk,velocity_anomaly,amount_anomaly,geo_anomaly',
            'severity' => 'required|in:low,medium,high,critical',
            'description' => 'required|string',
            'risk_score' => 'nullable|integer|min:0|max:100',
        ]);
        $validated['risk_score'] = $validated['risk_score'] ?? 50;
        $alert = FraudAlert::create($validated);
        return response()->json(['success' => true, 'data' => $alert->load(['merchant', 'transaction'])]);
    }

    public function updateAlert(Request $request, int $id)
    {
        $alert = FraudAlert::findOrFail($id);
        $validated = $request->validate([
            'status' => 'sometimes|in:open,investigating,resolved,false_positive',
            'assigned_to' => 'nullable|integer',
            'resolution_notes' => 'nullable|string',
        ]);
        if (isset($validated['status']) && in_array($validated['status'], ['resolved', 'false_positive'])) {
            $validated['resolved_at'] = now();
        }
        $alert->fill($validated)->save();
        return response()->json(['success' => true, 'data' => $alert]);
    }

    // Dashboard Stats
    public function getStats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_rules' => RiskRule::where('status', 'active')->count(),
                'total_events' => RiskEvent::where('resolved', false)->count(),
                'critical_alerts' => FraudAlert::where('severity', 'critical')->where('status', 'open')->count(),
                'high_alerts' => FraudAlert::where('severity', 'high')->where('status', 'open')->count(),
            ],
        ]);
    }
}

