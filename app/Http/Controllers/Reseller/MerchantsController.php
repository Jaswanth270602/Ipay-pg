<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MerchantsController extends Controller
{
    public function index(Request $request): View
    {
        $reseller = $request->user()->reseller;
        $merchants = $reseller
            ? $reseller->assignedMerchants()->get(['id', 'merchant_unique_id', 'name', 'email', 'status', 'approval_status'])
            : collect();

        return view('reseller.merchants', [
            'user' => $request->user(),
            'reseller' => $reseller,
            'merchants' => $merchants,
        ]);
    }
}
