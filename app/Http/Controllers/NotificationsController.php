<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $role = $user->isAdmin() ? 'admin' : 'merchant';

        $items = Notification::query()
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (Notification $n) {
                return [
                    'id' => $n->id,
                    'message' => $n->message,
                    'is_read' => (bool) $n->is_read,
                    'url' => $n->url,
                    'order_url' => $n->order_url,
                    'meta' => $n->meta,
                    'created_at' => $n->created_at?->toIso8601String(),
                    'created_at_human' => $n->created_at?->format('d-m-Y H:i:s'),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $user = auth()->user();
        $role = $user->isAdmin() ? 'admin' : 'merchant';

        $count = Notification::query()
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    public function markAsRead(Request $request): JsonResponse
    {
        $user = auth()->user();
        $role = $user->isAdmin() ? 'admin' : 'merchant';

        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => true,
                'updated' => 0,
            ]);
        }

        $updated = Notification::query()
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->whereIn('id', $ids)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'updated' => $updated,
        ]);
    }
}
