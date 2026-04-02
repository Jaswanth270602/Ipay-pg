<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        return view('reseller.profile', [
            'user' => $request->user(),
            'reseller' => $request->user()->reseller,
        ]);
    }
}

