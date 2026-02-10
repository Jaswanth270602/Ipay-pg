<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeysController extends Controller
{
    public function index()
    {
        $merchant = auth()->user()->merchant;
        $apiKeys = $merchant->apiKeys()->latest()->paginate(20);
        
        return view('merchant.api_keys.index', compact('merchant', 'apiKeys'));
    }

    public function getData(Request $request)
    {
        $merchant = auth()->user()->merchant;
        $apiKeys = $merchant->apiKeys()->latest()->paginate($request->get('per_page', 20));
        
        return response()->json([
            'data' => $apiKeys->items(),
            'pagination' => [
                'current_page' => $apiKeys->currentPage(),
                'last_page' => $apiKeys->lastPage(),
                'per_page' => $apiKeys->perPage(),
                'total' => $apiKeys->total(),
                'from' => $apiKeys->firstItem(),
                'to' => $apiKeys->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'mode' => 'required|in:test,live',
        ]);

        $merchant = auth()->user()->merchant;

        // Generate new API key
        $apiKey = ApiKey::generate(
            $merchant->id,
            $request->mode,
            $request->name
        );

        return response()->json([
            'success' => true,
            'message' => 'API key created successfully',
            'api_key' => [
                'id' => $apiKey->id,
                'key' => $apiKey->key,
                'secret' => $apiKey->secret,
                'name' => $apiKey->name,
                'mode' => $apiKey->mode,
            ]
        ]);
    }

    public function destroy($id)
    {
        $merchant = auth()->user()->merchant;
        $apiKey = ApiKey::where('merchant_id', $merchant->id)
            ->where('id', $id)
            ->firstOrFail();

        $apiKey->revoke();

        return response()->json([
            'success' => true,
            'message' => 'API key revoked successfully'
        ]);
    }

    public function regenerateSecret($id)
    {
        $merchant = auth()->user()->merchant;
        $apiKey = ApiKey::where('merchant_id', $merchant->id)
            ->where('id', $id)
            ->firstOrFail();

        $apiKey->secret = 'sk_' . $apiKey->mode . '_' . Str::random(32);
        $apiKey->save();

        return response()->json([
            'success' => true,
            'message' => 'Secret regenerated successfully',
            'secret' => $apiKey->secret
        ]);
    }
}

