<?php

namespace App\Http\Controllers\Sandbox;

use App\Http\Controllers\Controller;
use App\Services\Payments\YapilyService;
use Illuminate\Http\JsonResponse;

/**
 * Yapily Sandbox API controller.
 *
 * JSON only; no UI or redirects. All sandbox routes return 403 when
 * ENABLE_YAPILY_SANDBOX is false.
 */
class YapilyController extends Controller
{
    public function __construct(
        protected YapilyService $yapilyService
    ) {}

    /**
     * Return 403 when sandbox is disabled. Call from each action.
     */
    protected function ensureSandboxEnabled(): ?JsonResponse
    {
        if (!config('yapily.enabled', false)) {
            return response()->json([
                'error' => 'Sandbox Disabled',
                'message' => 'Yapily sandbox is not enabled. Set ENABLE_YAPILY_SANDBOX=true in .env to use sandbox endpoints.',
            ], 403);
        }

        return null;
    }

    /**
     * GET /api/sandbox/yapily/institutions
     * Fetch dummy banks; verify credentials. No side effects.
     */
    public function institutions(): JsonResponse
    {
        $forbidden = $this->ensureSandboxEnabled();
        if ($forbidden !== null) {
            return $forbidden;
        }

        $result = $this->yapilyService->getInstitutions();

        if ($result['success']) {
            return response()->json($result['data'], $result['status'] ?? 200);
        }

        $status = $result['status'] ?? 502;

        return response()->json([
            'error' => $result['error'] ?? 'Yapily request failed',
            'data' => $result['data'] ?? null,
        ], $status > 0 ? $status : 502);
    }
}
