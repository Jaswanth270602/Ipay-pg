<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminSettingsController extends Controller
{
    /**
     * Switch admin viewing mode (test/live).
     * This controls what data the admin sees - test or live.
     */
    public function switchMode(Request $request): JsonResponse
    {
        $mode = $request->input('mode');
        
        if (!in_array($mode, ['test', 'live'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid mode. Must be test or live.',
            ], 400);
        }
        
        // Store admin's viewing mode in session
        session(['admin_view_mode' => $mode]);
        
        return response()->json([
            'success' => true,
            'message' => 'Admin viewing mode switched to ' . $mode,
            'mode' => $mode,
        ]);
    }
    
    /**
     * Get current admin viewing mode.
     */
    public function getMode(): JsonResponse
    {
        $mode = session('admin_view_mode', 'test'); // Default to test
        
        return response()->json([
            'success' => true,
            'mode' => $mode,
        ]);
    }
}


