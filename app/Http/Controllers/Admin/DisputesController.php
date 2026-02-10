<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\DisputeEvidence;
use App\Models\DisputeTimeline;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DisputesController extends Controller
{
    /**
     * Display disputes index page
     */
    public function index(): View
    {
        return view('admin.disputes.index');
    }

    /**
     * Display dispute detail page
     */
    public function showView($id): View
    {
        return view('admin.disputes.show', compact('id'));
    }

    /**
     * Get disputes data with filters (Razorpay-style API)
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 20), 100);
            $query = Dispute::with(['merchant', 'transaction', 'payment']);

            // Status filter
            $status = $request->get('status');
            if ($status && $status !== 'all') {
                if ($status === 'closed') {
                    $query->closed();
                } else {
                    $query->where('status', $status);
                }
            }

            // Date range filter (only apply if both dates are provided and not empty)
            if ($request->has('from_date') && $request->has('to_date') && 
                !empty($request->get('from_date')) && !empty($request->get('to_date'))) {
                $fromDate = Carbon::parse($request->get('from_date'))->startOfDay();
                $toDate = Carbon::parse($request->get('to_date'))->endOfDay();
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            }

            // Payment ID filter
            if ($request->has('payment_id') && $request->get('payment_id')) {
                $query->where('payment_id', $request->get('payment_id'));
            }

            // Order ID filter
            if ($request->has('order_id') && $request->get('order_id')) {
                $query->where('order_id', 'like', "%{$request->get('order_id')}%");
            }

            // Search filter (dispute_id, payment_id, order_id)
            if ($request->has('search') && $request->get('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('dispute_id', 'like', "%{$search}%")
                      ->orWhere('payment_id', $search)
                      ->orWhere('order_id', 'like', "%{$search}%");
                });
            }

            // Merchant filter (admin only)
            if ($request->has('merchant_id') && $request->get('merchant_id')) {
                $query->where('merchant_id', $request->get('merchant_id'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'updated_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $allowedSortColumns = ['id', 'dispute_id', 'created_at', 'updated_at', 'due_by', 'amount', 'status'];
            if (in_array($sortBy, $allowedSortColumns)) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->orderBy('updated_at', 'desc');
            }

            $disputes = $query->paginate($perPage);

            // Transform data for frontend
            $data = $disputes->items();
            $transformedData = collect($data)->map(function($dispute) {
                return [
                    'id' => $dispute->id,
                    'dispute_id' => $dispute->dispute_id,
                    'merchant_id' => $dispute->merchant_id,
                    'merchant_name' => $dispute->merchant->name ?? 'N/A',
                    'payment_id' => $dispute->payment_id,
                    'order_id' => $dispute->order_id,
                    'transaction_id' => $dispute->transaction_id,
                    'card_network' => $dispute->card_network,
                    'reason' => $dispute->reason,
                    'status' => $dispute->status,
                    'amount' => $dispute->amount,
                    'currency' => $dispute->currency ?: 'INR',
                    'due_by' => $dispute->due_by ? $dispute->due_by->toIso8601String() : null,
                    'due_by_formatted' => $dispute->due_by ? $dispute->due_by->format('M d, Y H:i') : null,
                    'due_by_human' => $dispute->due_by ? $dispute->due_by->diffForHumans() : null,
                    'is_past_due' => $dispute->isPastDue(),
                    'evidence_submitted' => $dispute->evidence_submitted,
                    'dispute_fee' => $dispute->dispute_fee,
                    'frozen_amount' => $dispute->frozen_amount,
                    'created_at' => $dispute->created_at->toIso8601String(),
                    'updated_at' => $dispute->updated_at->toIso8601String(),
                    'created_at_formatted' => $dispute->created_at->format('M d, Y H:i'),
                    'updated_at_formatted' => $dispute->updated_at->format('M d, Y H:i'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'pagination' => [
                    'current_page' => $disputes->currentPage(),
                    'per_page' => $disputes->perPage(),
                    'total' => $disputes->total(),
                    'last_page' => $disputes->lastPage(),
                    'from' => $disputes->firstItem(),
                    'to' => $disputes->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch disputes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get disputes summary (Razorpay-style)
     */
    public function getSummary(Request $request): JsonResponse
    {
        try {
            $query = Dispute::query();

            // Apply merchant filter if provided
            if ($request->has('merchant_id') && $request->get('merchant_id')) {
                $query->where('merchant_id', $request->get('merchant_id'));
            }

            // Due today
            $dueTodayQuery = (clone $query)->actionRequired()->dueToday();
            $dueTodayCount = $dueTodayQuery->count();
            $dueTodayAmount = $dueTodayQuery->sum('amount');

            // Due tomorrow
            $dueTomorrowQuery = (clone $query)->actionRequired()->dueTomorrow();
            $dueTomorrowCount = $dueTomorrowQuery->count();
            $dueTomorrowAmount = $dueTomorrowQuery->sum('amount');

            // Insufficient evidence
            $insufficientEvidenceQuery = (clone $query)->insufficientEvidence();
            $insufficientEvidenceCount = $insufficientEvidenceQuery->count();
            $insufficientEvidenceAmount = $insufficientEvidenceQuery->sum('amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'due_today_count' => $dueTodayCount,
                    'due_today_amount' => (float) $dueTodayAmount,
                    'due_tomorrow_count' => $dueTomorrowCount,
                    'due_tomorrow_amount' => (float) $dueTomorrowAmount,
                    'insufficient_evidence_count' => $insufficientEvidenceCount,
                    'insufficient_evidence_amount' => (float) $insufficientEvidenceAmount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch summary: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single dispute details (JSON API)
     */
    public function show($id): JsonResponse
    {
        try {
            $dispute = Dispute::with([
                'merchant',
                'transaction',
                'evidence',
                'timeline'
            ])->findOrFail($id);

            $transformedDispute = [
                'id' => $dispute->id,
                'merchant_id' => $dispute->merchant_id,
                'merchant_name' => $dispute->merchant->name ?? 'N/A',
                'payment_id' => $dispute->payment_id,
                'order_id' => $dispute->order_id,
                'transaction_id' => $dispute->transaction_id,
                'card_network' => $dispute->card_network,
                'reason' => $dispute->reason,
                'reason_formatted' => ucwords(str_replace('_', ' ', $dispute->reason)),
                'status' => $dispute->status,
                'status_formatted' => ucwords(str_replace('_', ' ', $dispute->status)),
                'amount' => $dispute->amount,
                'currency' => $dispute->currency ?: 'INR',
                'due_by' => $dispute->due_by ? $dispute->due_by->toIso8601String() : null,
                'due_by_formatted' => $dispute->due_by ? $dispute->due_by->format('M d, Y H:i') : null,
                'evidence_submitted' => $dispute->evidence_submitted,
                'dispute_fee' => $dispute->dispute_fee,
                'frozen_amount' => $dispute->frozen_amount,
                'internal_notes' => $dispute->internal_notes,
                'can_upload_evidence' => $dispute->canUploadEvidence(),
                'can_submit' => $dispute->canSubmit(),
                'is_past_due' => $dispute->isPastDue(),
                'created_at' => $dispute->created_at->toIso8601String(),
                'updated_at' => $dispute->updated_at->toIso8601String(),
                'evidence' => $dispute->evidence->map(function($evidence) {
                    return [
                        'id' => $evidence->id,
                        'document_type' => $evidence->document_type,
                        'document_type_formatted' => ucwords(str_replace('_', ' ', $evidence->document_type)),
                        'file_name' => $evidence->file_name,
                        'file_url' => $evidence->file_url,
                        'file_size' => $evidence->file_size,
                        'uploaded_at' => $evidence->uploaded_at->toIso8601String(),
                    ];
                }),
                'timeline' => $dispute->timeline->sortByDesc('created_at')->map(function($event) {
                    return [
                        'id' => $event->id,
                        'event' => $event->event,
                        'event_formatted' => ucwords(str_replace('_', ' ', $event->event)),
                        'notes' => $event->notes,
                        'created_at' => $event->created_at->toIso8601String(),
                        'created_at_formatted' => $event->created_at->format('M d, Y H:i'),
                        'metadata' => $event->metadata,
                    ];
                })->values(),
                'payment_details' => $dispute->transaction ? [
                    'id' => $dispute->transaction->id,
                    'txn_id' => $dispute->transaction->txn_id ?? null,
                    'amount' => $dispute->transaction->amount ?? null,
                    'status' => $dispute->transaction->status ?? null,
                    'payment_method' => $dispute->transaction->payment_method ?? null,
                    'created_at' => $dispute->transaction->created_at ? $dispute->transaction->created_at->toIso8601String() : null,
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'data' => $transformedDispute,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching dispute: ' . $e->getMessage(), [
                'exception' => $e,
                'dispute_id' => $id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dispute: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload evidence document
     */
    public function uploadEvidence(Request $request, $id): JsonResponse
    {
        try {
            $dispute = Dispute::findOrFail($id);

            // Check if evidence can be uploaded
            if (!$dispute->canUploadEvidence()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evidence cannot be uploaded for this dispute. Status must be action_required and not yet submitted.',
                ], 400);
            }

            $request->validate([
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
                'document_type' => 'required|in:invoice,delivery_proof,communication,refund_proof,other',
            ]);

            $file = $request->file('file');
            $documentType = $request->get('document_type');
            
            // Generate unique filename
            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('dispute_evidence', $fileName, 'local');

            // Create evidence record
            $evidence = DisputeEvidence::create([
                'dispute_id' => $dispute->id,
                'document_type' => $documentType,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_at' => now(),
            ]);

            // Create timeline entry
            DisputeTimeline::create([
                'dispute_id' => $dispute->id,
                'event' => 'evidence_uploaded',
                'notes' => "Evidence uploaded: {$file->getClientOriginalName()} ({$documentType})",
                'changed_by_type' => auth()->user()->isAdmin() ? 'admin' : 'merchant',
                'changed_by_id' => auth()->id(),
                'metadata' => [
                    'evidence_id' => $evidence->id,
                    'document_type' => $documentType,
                    'file_name' => $file->getClientOriginalName(),
                ],
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Evidence uploaded successfully',
                'data' => [
                    'id' => $evidence->id,
                    'document_type' => $evidence->document_type,
                    'file_name' => $evidence->file_name,
                    'file_url' => $evidence->file_url,
                    'uploaded_at' => $evidence->uploaded_at->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload evidence: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete evidence document
     */
    public function deleteEvidence(Request $request, $id, $evidenceId): JsonResponse
    {
        try {
            $dispute = Dispute::findOrFail($id);
            $evidence = DisputeEvidence::where('dispute_id', $dispute->id)
                ->findOrFail($evidenceId);

            if (!$dispute->canUploadEvidence()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evidence cannot be deleted. Dispute is already submitted.',
                ], 400);
            }

            // Delete file
            if (Storage::disk('local')->exists($evidence->file_path)) {
                Storage::disk('local')->delete($evidence->file_path);
            }

            // Create timeline entry
            DisputeTimeline::create([
                'dispute_id' => $dispute->id,
                'event' => 'evidence_deleted',
                'notes' => "Evidence deleted: {$evidence->file_name}",
                'changed_by_type' => auth()->user()->isAdmin() ? 'admin' : 'merchant',
                'changed_by_id' => auth()->id(),
                'created_at' => now(),
            ]);

            // Delete record
            $evidence->delete();

            return response()->json([
                'success' => true,
                'message' => 'Evidence deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete evidence: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit dispute evidence (locks evidence and submits to bank)
     */
    public function submit(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $dispute = Dispute::findOrFail($id);

            if (!$dispute->canSubmit()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dispute cannot be submitted. Status must be action_required, not yet submitted, and must have at least one evidence document.',
                ], 400);
            }

            // Update dispute
            $dispute->evidence_submitted = true;
            $dispute->status = 'under_review';
            $dispute->save();

            // Create timeline entry
            DisputeTimeline::create([
                'dispute_id' => $dispute->id,
                'event' => 'submitted',
                'notes' => 'Evidence submitted to card network',
                'changed_by_type' => auth()->user()->isAdmin() ? 'admin' : 'merchant',
                'changed_by_id' => auth()->id(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Evidence submitted successfully. Dispute is now under review.',
                'data' => [
                    'dispute_id' => $dispute->dispute_id,
                    'status' => $dispute->status,
                    'evidence_submitted' => $dispute->evidence_submitted,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit evidence: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update dispute status (system/admin only - for webhooks)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $dispute = Dispute::findOrFail($id);

            $request->validate([
                'status' => 'required|in:action_required,under_review,insufficient_evidence,won,lost,closed',
                'notes' => 'nullable|string',
            ]);

            $oldStatus = $dispute->status;
            $dispute->status = $request->get('status');
            
            // Handle business logic based on status
            if ($request->get('status') === 'lost') {
                // Charge dispute fee and debit merchant
                $dispute->dispute_fee = $dispute->amount * 0.02; // 2% dispute fee (example)
                // TODO: Implement actual balance debit logic
            } elseif ($request->get('status') === 'won') {
                // Release frozen amount
                // TODO: Implement actual balance release logic
                $dispute->frozen_amount = 0;
            }

            $dispute->save();

            // Create timeline entry (will also be created by model observer, but add custom notes if provided)
            if ($request->has('notes')) {
                DisputeTimeline::create([
                    'dispute_id' => $dispute->id,
                    'event' => 'status_changed',
                    'notes' => $request->get('notes'),
                    'changed_by_type' => 'admin',
                    'changed_by_id' => auth()->id(),
                    'metadata' => [
                        'old_status' => $oldStatus,
                        'new_status' => $dispute->status,
                    ],
                    'created_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => [
                    'dispute_id' => $dispute->dispute_id,
                    'status' => $dispute->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export disputes to CSV
     */
    public function export(Request $request): StreamedResponse
    {
        try {
            $query = Dispute::with(['merchant', 'transaction', 'payment']);

            // Apply same filters as getData
            $status = $request->get('status');
            if ($status && $status !== 'all') {
                if ($status === 'closed') {
                    $query->closed();
                } else {
                    $query->where('status', $status);
                }
            }

            if ($request->has('from_date') && $request->has('to_date')) {
                $fromDate = Carbon::parse($request->get('from_date'))->startOfDay();
                $toDate = Carbon::parse($request->get('to_date'))->endOfDay();
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            }

            if ($request->has('payment_id') && $request->get('payment_id')) {
                $query->where('payment_id', $request->get('payment_id'));
            }

            if ($request->has('order_id') && $request->get('order_id')) {
                $query->where('order_id', 'like', "%{$request->get('order_id')}%");
            }

            if ($request->has('search') && $request->get('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('dispute_id', 'like', "%{$search}%")
                      ->orWhere('payment_id', $search)
                      ->orWhere('order_id', 'like', "%{$search}%");
                });
            }

            if ($request->has('merchant_id') && $request->get('merchant_id')) {
                $query->where('merchant_id', $request->get('merchant_id'));
            }

            $disputes = $query->orderBy('updated_at', 'desc')->get();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="disputes_' . now()->format('Y-m-d_His') . '.csv"',
            ];

            $callback = function() use ($disputes) {
                $file = fopen('php://output', 'w');
                
                fputcsv($file, [
                    'Dispute ID', 'Merchant ID', 'Merchant Name', 'Payment ID', 'Order ID',
                    'Transaction ID', 'Card Network', 'Reason', 'Status', 'Amount', 'Currency',
                    'Due By', 'Evidence Submitted', 'Dispute Fee', 'Frozen Amount',
                    'Created At', 'Updated At'
                ]);

                foreach ($disputes as $dispute) {
                    fputcsv($file, [
                        $dispute->dispute_id,
                        $dispute->merchant_id,
                        $dispute->merchant->name ?? 'N/A',
                        $dispute->payment_id ?? '-',
                        $dispute->order_id ?? '-',
                        $dispute->transaction_id ?? '-',
                        $dispute->card_network ?? '-',
                        $dispute->reason,
                        $dispute->status,
                        number_format($dispute->amount, 2),
                        $dispute->currency ?: 'INR',
                        $dispute->due_by ? $dispute->due_by->format('Y-m-d H:i:s') : '-',
                        $dispute->evidence_submitted ? 'Yes' : 'No',
                        number_format($dispute->dispute_fee, 2),
                        number_format($dispute->frozen_amount, 2),
                        $dispute->created_at->format('Y-m-d H:i:s'),
                        $dispute->updated_at->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export disputes: ' . $e->getMessage(),
            ], 500);
        }
    }
}

