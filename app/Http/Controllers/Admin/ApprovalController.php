<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MerchantTdrApproval;
use App\Models\PgRefundApproval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    /**
     * Display the Merchant TDR Approvals page.
     */
    public function merchantTdr(): View
    {
        return view('admin.approvals.merchant-tdr');
    }

    /**
     * Display the PG Refund Approvals page.
     */
    public function pgRefunds(): View
    {
        return view('admin.approvals.pg-refunds');
    }

    /**
     * Get Merchant TDR Approvals data with filters and pagination.
     */
    public function getMerchantTdrData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->integer('per_page', 5), 50);

            $query = MerchantTdrApproval::query()->with(['creator', 'approver', 'merchant']);

            // Status filter
            if ($request->filled('status') && $request->get('status') !== 'all') {
                $query->where('is_approved', $request->get('status'));
            }

            // Approval ID filter
            if ($request->filled('approval_id')) {
                $query->where('id', $request->get('approval_id'));
            }

            // Created By filter
            if ($request->filled('created_by')) {
                $query->where('created_by', $request->get('created_by'));
            }

            // Merchant ID filter
            if ($request->filled('merchant_id')) {
                $query->where('merchant_id', $request->get('merchant_id'));
            }

            // Merchant Name filter
            if ($request->filled('merchant_name')) {
                $query->where('merchant_name', 'like', '%' . $request->get('merchant_name') . '%');
            }

            // Model ID filter
            if ($request->filled('model_id')) {
                $query->where('model_id', $request->get('model_id'));
            }

            // Model Name filter
            if ($request->filled('model_name')) {
                $query->where('model_name', 'like', '%' . $request->get('model_name') . '%');
            }

            // Operation filter
            if ($request->filled('operation')) {
                $query->where('operation', 'like', '%' . $request->get('operation') . '%');
            }

            // Created At filter (handle MM/DD/YYYY format)
            if ($request->filled('created_at')) {
                $createdAt = $request->get('created_at');
                // Try to parse MM/DD/YYYY format
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $createdAt, $matches)) {
                    $date = $matches[3] . '-' . $matches[1] . '-' . $matches[2];
                    $query->whereDate('created_at', $date);
                } else {
                    // Fallback to direct date match
                    $query->whereDate('created_at', $createdAt);
                }
            }

            // Approved Date filter (handle MM/DD/YYYY format)
            if ($request->filled('approved_at')) {
                $approvedAt = $request->get('approved_at');
                // Try to parse MM/DD/YYYY format
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $approvedAt, $matches)) {
                    $date = $matches[3] . '-' . $matches[1] . '-' . $matches[2];
                    $query->whereDate('approved_at', $date);
                } else {
                    // Fallback to direct date match
                    $query->whereDate('approved_at', $approvedAt);
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $approvals = $query->paginate($perPage);

            // Transform data
            $data = $approvals->map(function ($approval) {
                return [
                    'id' => $approval->id,
                    'approval_id' => $approval->id,
                    'created_by' => $approval->creator ? $approval->creator->name : 'N/A',
                    'merchant_id' => $approval->merchant_id,
                    'merchant_name' => $approval->merchant_name ?? 'N/A',
                    'model_id' => $approval->model_id,
                    'model_name' => $approval->model_name ?? 'N/A',
                    'operation' => $approval->operation ?? 'N/A',
                    'previous_changes' => $approval->previous_changes ? json_encode($approval->previous_changes) : 'N/A',
                    'changes' => $approval->changes ? json_encode($approval->changes) : 'N/A',
                    'is_approved' => $approval->is_approved,
                    'approved_by' => $approval->approver ? $approval->approver->name : null,
                    'approved_at' => $approval->approved_at ? $approval->approved_at->format('m/d/Y H:i:s') : null,
                    'created_at' => $approval->created_at ? $approval->created_at->format('m/d/Y H:i:s') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data->toArray(),
                'pagination' => [
                    'current_page' => $approvals->currentPage(),
                    'per_page' => $approvals->perPage(),
                    'total' => $approvals->total(),
                    'last_page' => $approvals->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch merchant TDR approvals: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get PG Refund Approvals data with filters and pagination.
     */
    public function getPgRefundData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->integer('per_page', 5), 50);

            $query = PgRefundApproval::query()->with(['creator', 'approver', 'merchant']);

            // Status filter
            if ($request->filled('status') && $request->get('status') !== 'all') {
                $query->where('is_approved', $request->get('status'));
            }

            // Approval ID filter
            if ($request->filled('approval_id')) {
                $query->where('id', $request->get('approval_id'));
            }

            // Created By filter - search by creator name
            if ($request->filled('created_by')) {
                $createdByName = $request->get('created_by');
                $userIds = \App\Models\User::where('name', 'like', '%' . $createdByName . '%')->pluck('id');
                if ($userIds->isNotEmpty()) {
                    $query->whereIn('created_by', $userIds);
                } else {
                    // If no users found, return empty result
                    $query->whereRaw('1 = 0');
                }
            }

            // Merchant ID filter
            if ($request->filled('merchant_id')) {
                $query->where('merchant_id', $request->get('merchant_id'));
            }

            // Merchant Name filter
            if ($request->filled('merchant_name')) {
                $query->where('merchant_name', 'like', '%' . $request->get('merchant_name') . '%');
            }

            // Model ID filter
            if ($request->filled('model_id')) {
                $query->where('model_id', $request->get('model_id'));
            }

            // Model Name filter
            if ($request->filled('model_name')) {
                $query->where('model_name', 'like', '%' . $request->get('model_name') . '%');
            }

            // Operation filter
            if ($request->filled('operation')) {
                $query->where('operation', 'like', '%' . $request->get('operation') . '%');
            }

            // Created At filter (handle MM/DD/YYYY format)
            if ($request->filled('created_at')) {
                $createdAt = $request->get('created_at');
                // Try to parse MM/DD/YYYY format
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $createdAt, $matches)) {
                    $date = $matches[3] . '-' . $matches[1] . '-' . $matches[2];
                    $query->whereDate('created_at', $date);
                } else {
                    // Fallback to direct date match
                    $query->whereDate('created_at', $createdAt);
                }
            }

            // Approved Date filter (handle MM/DD/YYYY format)
            if ($request->filled('approved_at')) {
                $approvedAt = $request->get('approved_at');
                // Try to parse MM/DD/YYYY format
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $approvedAt, $matches)) {
                    $date = $matches[3] . '-' . $matches[1] . '-' . $matches[2];
                    $query->whereDate('approved_at', $date);
                } else {
                    // Fallback to direct date match
                    $query->whereDate('approved_at', $approvedAt);
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $approvals = $query->paginate($perPage);

            // Transform data
            $data = $approvals->map(function ($approval) {
                return [
                    'id' => $approval->id,
                    'approval_id' => $approval->id,
                    'created_by' => $approval->creator ? $approval->creator->name : 'N/A',
                    'merchant_id' => $approval->merchant_id,
                    'merchant_name' => $approval->merchant_name ?? 'N/A',
                    'model_id' => $approval->model_id,
                    'model_name' => $approval->model_name ?? 'N/A',
                    'operation' => $approval->operation ?? 'N/A',
                    'previous_changes' => $approval->previous_changes ? json_encode($approval->previous_changes) : 'N/A',
                    'changes' => $approval->changes ? json_encode($approval->changes) : 'N/A',
                    'is_approved' => $approval->is_approved,
                    'approved_by' => $approval->approver ? $approval->approver->name : null,
                    'approved_at' => $approval->approved_at ? $approval->approved_at->format('m/d/Y H:i:s') : null,
                    'created_at' => $approval->created_at ? $approval->created_at->format('m/d/Y H:i:s') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data->toArray(),
                'pagination' => [
                    'current_page' => $approvals->currentPage(),
                    'per_page' => $approvals->perPage(),
                    'total' => $approvals->total(),
                    'last_page' => $approvals->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch PG refund approvals: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve Merchant TDR approval request.
     */
    public function approveMerchantTdr(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'approval_notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $approval = MerchantTdrApproval::findOrFail($id);
            $approval->update([
                'is_approved' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->input('approval_notes'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Merchant TDR approval request approved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve merchant TDR: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject Merchant TDR approval request.
     */
    public function rejectMerchantTdr(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'approval_notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $approval = MerchantTdrApproval::findOrFail($id);
            $approval->update([
                'is_approved' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->input('approval_notes'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Merchant TDR approval request rejected successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject merchant TDR: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve PG Refund approval request.
     */
    public function approvePgRefund(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'approval_notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $approval = PgRefundApproval::findOrFail($id);
            $approval->update([
                'is_approved' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->input('approval_notes'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PG Refund approval request approved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve PG refund: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject PG Refund approval request.
     */
    public function rejectPgRefund(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'approval_notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $approval = PgRefundApproval::findOrFail($id);
            $approval->update([
                'is_approved' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->input('approval_notes'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PG Refund approval request rejected successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject PG refund: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk approve/reject Merchant TDR approvals.
     */
    public function bulkMerchantTdrAction(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:merchant_tdr_approvals,id',
                'action' => 'required|in:approve,reject',
                'approval_notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $ids = $request->input('ids');
            $action = $request->input('action');
            $status = $action === 'approve' ? 'approved' : 'rejected';

            MerchantTdrApproval::whereIn('id', $ids)->update([
                'is_approved' => $status,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->input('approval_notes'),
            ]);

            return response()->json([
                'success' => true,
                'message' => count($ids) . ' Merchant TDR approval(s) ' . $action . 'd successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk action: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk approve/reject PG Refund approvals.
     */
    public function bulkPgRefundAction(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:pg_refund_approvals,id',
                'action' => 'required|in:approve,reject',
                'approval_notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $ids = $request->input('ids');
            $action = $request->input('action');
            $status = $action === 'approve' ? 'approved' : 'rejected';

            PgRefundApproval::whereIn('id', $ids)->update([
                'is_approved' => $status,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->input('approval_notes'),
            ]);

            return response()->json([
                'success' => true,
                'message' => count($ids) . ' PG Refund approval(s) ' . $action . 'd successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk action: ' . $e->getMessage(),
            ], 500);
        }
    }
}
