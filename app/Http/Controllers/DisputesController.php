<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisputesController extends Controller
{
    public function index(): View
    {
        return view('merchant.disputes.index');
    }

    public function getData(Request $request)
    {
        $merchant = $request->user()->merchant;
        $query = Dispute::where('merchant_id', $merchant->id)
            ->orderByDesc('created_at');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $disputes = $query->paginate(15);
        
        // Format disputes for display
        $disputes->getCollection()->transform(function ($dispute) {
            return [
                'id' => $dispute->id,
                'dispute_id' => $dispute->dispute_id ?? $dispute->id,
                'merchant_id' => $dispute->merchant_id,
                'payment_id' => $dispute->payment_id,
                'order_id' => $dispute->order_id,
                'transaction_id' => $dispute->transaction_id,
                'card_network' => $dispute->card_network,
                'reason' => $dispute->reason,
                'reason_formatted' => ucwords(str_replace('_', ' ', $dispute->reason)),
                'status' => $dispute->status,
                'status_formatted' => ucwords(str_replace('_', ' ', $dispute->status)),
                'amount' => $dispute->amount,
                'currency' => $dispute->currency ?? 'INR',
                'due_by' => $dispute->due_by,
                'due_by_formatted' => $dispute->due_by ? $dispute->due_by->format('M d, Y H:i') : null,
                'evidence_submitted' => $dispute->evidence_submitted,
                'dispute_fee' => $dispute->dispute_fee,
                'frozen_amount' => $dispute->frozen_amount,
                'created_at' => $dispute->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $dispute->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $disputes,
        ]);
    }
}


