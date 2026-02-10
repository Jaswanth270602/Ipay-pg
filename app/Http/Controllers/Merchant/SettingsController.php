<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $merchant = $user->merchant;
        $apiKeys = $merchant->apiKeys()->latest()->get();
        return view('merchant.settings.index', compact('merchant','apiKeys'));
    }

    public function switchMode(Request $request)
    {
        $request->validate(['mode' => 'required|in:test,live']);
        $merchant = $request->user()->merchant;
        $merchant->test_mode = $request->mode === 'test';
        $merchant->save();
        return response()->json(['success'=>true,'mode'=>$request->mode]);
    }

    public function updateWebhook(Request $request)
    {
        $request->validate(['webhook_url' => 'required|url|max:500']);
        $merchant = $request->user()->merchant;
        $merchant->webhook_url = $request->webhook_url;
        $merchant->save();
        return back()->with('success','Webhook URL saved');
    }
}

 