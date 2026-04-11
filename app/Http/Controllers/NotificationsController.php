<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    private function payloadForUserIdAndRole(int $userId, string $role): JsonResponse
    {
        $items = Notification::query()
            ->where('user_id', $userId)
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

    private function unreadCountForUserIdAndRole(int $userId, string $role): JsonResponse
    {
        $count = Notification::query()
            ->where('user_id', $userId)
            ->where('role', $role)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    private function markAsReadForUserIdAndRole(Request $request, int $userId, string $role): JsonResponse
    {
        $ids = $request->input('ids', []);
        $markAll = (bool) $request->input('all', false);

        $baseQuery = Notification::query()
            ->where('user_id', $userId)
            ->where('role', $role)
            ->where('is_read', false);

        if ($markAll) {
            $updated = $baseQuery->update(['is_read' => true]);
        } elseif (is_array($ids) && !empty($ids)) {
            $updated = $baseQuery->whereIn('id', $ids)->update(['is_read' => true]);
        } else {
            $updated = 0;
        }

        return response()->json([
            'success' => true,
            'updated' => $updated,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $role = $user->isAdmin() ? 'admin' : 'merchant';
        return $this->payloadForUserIdAndRole((int) $user->id, $role);
    }

    public function unreadCount(): JsonResponse
    {
        $user = auth()->user();
        $role = $user->isAdmin() ? 'admin' : 'merchant';
        return $this->unreadCountForUserIdAndRole((int) $user->id, $role);
    }

    public function markAsRead(Request $request): JsonResponse
    {
        $user = auth()->user();
        $role = $user->isAdmin() ? 'admin' : 'merchant';
        return $this->markAsReadForUserIdAndRole($request, (int) $user->id, $role);
    }

}
