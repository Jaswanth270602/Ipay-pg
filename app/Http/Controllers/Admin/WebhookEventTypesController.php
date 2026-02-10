<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookEventType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class WebhookEventTypesController extends Controller
{
    public function index(): View
    {
        return view('admin.webhook-event-types.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $category = $request->get('category');
        $enabled = $request->get('enabled');
        $search = $request->get('search');

        $query = WebhookEventType::query()->orderBy('sort_order')->orderBy('name');

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($enabled !== null && $enabled !== '') {
            $query->where('enabled', filter_var($enabled, FILTER_VALIDATE_BOOLEAN));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('event_key', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $eventTypes = $query->get();

        return response()->json([
            'success' => true,
            'data' => $eventTypes,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'enabled' => 'sometimes|required|boolean',
            'sort_order' => 'sometimes|required|integer',
        ]);

        $eventType = WebhookEventType::findOrFail($id);
        $eventType->update($request->only(['name', 'description', 'enabled', 'sort_order']));

        return response()->json([
            'success' => true,
            'message' => 'Webhook event type updated successfully',
            'data' => $eventType,
        ]);
    }

    public function toggle(Request $request, $id): JsonResponse
    {
        $eventType = WebhookEventType::findOrFail($id);
        $eventType->enabled = !$eventType->enabled;
        $eventType->save();

        return response()->json([
            'success' => true,
            'message' => 'Webhook event type ' . ($eventType->enabled ? 'enabled' : 'disabled') . ' successfully',
            'data' => $eventType,
        ]);
    }
}

