<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class FederalDirectVPAController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin federal direct VPA payments page accessed', ['user_id' => auth()->id()]);
        return view('admin.payments.federal-direct-vpa');
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 5), 50);
            
            // For now, return empty data - you'll need to create a federal_direct_vpa_payments table
            $data = [];
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch federal direct VPA payments',
            ], 500);
        }
    }
}

