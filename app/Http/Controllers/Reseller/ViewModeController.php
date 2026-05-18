<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Support\PaymentViewMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ViewModeController extends Controller
{
    public function switchMode(Request $request): JsonResponse
    {
        $mode = $request->input('mode');

        if (! in_array($mode, ['test', 'live'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid mode. Must be test or live.',
            ], 400);
        }

        PaymentViewMode::setSessionMode('reseller', $mode);

        return response()->json([
            'success' => true,
            'message' => 'Reseller viewing mode switched to '.$mode,
            'mode' => $mode,
        ]);
    }

    public function getMode(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'mode' => PaymentViewMode::isTestMode() ? 'test' : 'live',
        ]);
    }
}
