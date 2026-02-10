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

    public function store(Request $request)
    {
        $merchant = $request->user()->merchant;
        $validated = $request->validate([
            'transaction_id' => 'nullable', // Can be txn_id string or numeric ID
            'payment_id' => 'nullable|string',
            'order_id' => 'nullable|string',
            'reason' => 'required|string|in:fraud,product_not_received,product_not_as_described,duplicate_charge,refund_not_processed,subscription_canceled,no_authorization',
            'amount' => 'required|numeric|min:0',
            'card_network' => 'nullable|string|in:VISA,MASTERCARD,RUPAY',
            'currency' => 'nullable|string|size:3',
            'due_by' => 'nullable|date',
            'internal_notes' => 'nullable|string',
        ]);

        // If transaction_id is provided, try to find the transaction
        $transactionId = null;
        if (!empty($validated['transaction_id'])) {
            $txnInput = trim($validated['transaction_id']);
            // Try to find by txn_id first (string like "TXN_..." or "E81")
            $transaction = \App\Models\Transaction::where('merchant_id', $merchant->id)
                ->where('txn_id', $txnInput)
                ->first();
            
            // If not found by txn_id, try by numeric ID
            if (!$transaction && is_numeric($txnInput)) {
                $transaction = \App\Models\Transaction::where('merchant_id', $merchant->id)
                    ->where('id', $txnInput)
                    ->first();
            }
            
            if ($transaction) {
                $transactionId = $transaction->id;
            }
            // If transaction not found, we'll leave it null (optional field)
        }

        // For merchant-created disputes, set due_by to 7 days from now if not provided
        $dueBy = $validated['due_by'] ?? \Carbon\Carbon::now()->addDays(7);

        $dispute = Dispute::create([
            'merchant_id' => $merchant->id,
            'transaction_id' => $transactionId,
            'payment_id' => $validated['payment_id'] ?? null,
            'order_id' => $validated['order_id'] ?? null,
            'card_network' => $validated['card_network'] ?? null,
            'reason' => $validated['reason'],
            'status' => 'action_required', // Razorpay-style status
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'INR',
            'due_by' => $dueBy,
            'evidence_submitted' => false,
            'frozen_amount' => $validated['amount'], // Freeze the disputed amount
            'dispute_fee' => 0,
            'internal_notes' => $validated['internal_notes'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $dispute]);
    }

    public function indexAdmin(): View
    {
        return view('admin.disputes.index');
    }

    public function getDataAdmin(Request $request)
    {
        $query = Dispute::query()->orderByDesc('created_at');
        if ($request->merchant_id) {
            $query->where('merchant_id', $request->merchant_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        return response()->json([
            'success' => true,
            'data' => $query->paginate(20),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,needs_evidence,won,lost,closed',
            'evidence_url' => 'nullable|url',
            'notes' => 'nullable|string',
        ]);

        $dispute = Dispute::findOrFail($id);
        $dispute->fill($validated);
        $dispute->save();

        return response()->json(['success' => true, 'data' => $dispute]);
    }
}


