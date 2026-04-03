<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\SettlementEngine;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SettlementCronController extends Controller
{
    public function index(Request $request): View
    {
        $merchant = $request->user()->merchant;

        return view('merchant.settlements.cron', [
            'merchant' => $merchant,
            'cronPingBaseUrl' => url('/cron/schedule'),
        ]);
    }

    /**
     * Run the settlement engine for the authenticated merchant only (same logic as CLI batch).
     */
    public function run(Request $request, SettlementEngine $engine): JsonResponse
    {
        $merchant = $request->user()->merchant;

        if ($merchant->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your merchant account must be active to process settlements.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'date' => ['nullable', 'date'],
            'mode' => ['nullable', 'in:test,live,all'],
            'dry_run' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::now();

        $modeInput = $request->input('mode', 'all');
        $mode = $modeInput === 'all' ? null : $modeInput;

        $dryRun = filter_var($request->input('dry_run', false), FILTER_VALIDATE_BOOLEAN);

        try {
            $results = $engine->processDailySettlements($date, $merchant->id, $mode, $dryRun);

            if ($results === []) {
                return response()->json([
                    'success' => true,
                    'message' => 'No settlement run was executed for your account.',
                    'data' => null,
                ]);
            }

            $result = $results[0];

            $message = $result['message']
                ?? ($result['created'] ?? false
                    ? 'Settlement batch processed.'
                    : 'No transactions were ready for settlement for the selected options.');

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
