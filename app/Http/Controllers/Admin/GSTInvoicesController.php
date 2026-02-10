<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GSTInvoice;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class GSTInvoicesController extends Controller
{
    /**
     * Display the GST Invoices Report page.
     */
    public function index(): View
    {
        return view('admin.reports.gst-invoices.index');
    }

    /**
     * Get GST Invoices data with filters and pagination.
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->integer('per_page', 5), 50);

            $query = GSTInvoice::query()->with('merchant');

            // Filters
            if ($request->filled('invoice_number')) {
                $query->where('invoice_number', 'like', '%' . $request->get('invoice_number') . '%');
            }

            if ($request->filled('month') && $request->get('month') !== 'all') {
                $query->where('month', $request->get('month'));
            }

            if ($request->filled('year')) {
                $query->where('year', $request->get('year'));
            }

            if ($request->filled('merchant_id')) {
                $query->where('merchant_id', $request->get('merchant_id'));
            }

            if ($request->filled('gst_provided_by')) {
                $query->where('gst_provided_by', 'like', '%' . $request->get('gst_provided_by') . '%');
            }

            if ($request->filled('gst_payer_name')) {
                $query->where('gst_payer_name', 'like', '%' . $request->get('gst_payer_name') . '%');
            }

            if ($request->filled('payer_gstin')) {
                $query->where('payer_gstin', 'like', '%' . $request->get('payer_gstin') . '%');
            }

            if ($request->filled('payer_gstin_state') && $request->get('payer_gstin_state') !== 'all') {
                $query->where('payer_gstin_state', $request->get('payer_gstin_state'));
            }

            if ($request->filled('invoice_date')) {
                $query->whereDate('invoice_date', $request->get('invoice_date'));
            }

            // Amount filters
            if ($request->filled('invoice_value_min')) {
                $query->where('invoice_value', '>=', $request->get('invoice_value_min'));
            }

            if ($request->filled('invoice_value_max')) {
                $query->where('invoice_value', '<=', $request->get('invoice_value_max'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $invoices = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $invoices->items(),
                'pagination' => [
                    'current_page' => $invoices->currentPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total(),
                    'last_page' => $invoices->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GST invoices: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get GST states for dropdown.
     */
    public function getGSTStates(): JsonResponse
    {
        $states = GSTInvoice::whereNotNull('payer_gstin_state')
            ->distinct()
            ->pluck('payer_gstin_state')
            ->filter()
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $states,
        ]);
    }

    /**
     * Get merchants for dropdown.
     */
    public function getMerchants(): JsonResponse
    {
        $merchants = Merchant::select('id', 'business_name', 'merchant_id')
            ->orderBy('business_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $merchants,
        ]);
    }

    /**
     * Get a single GST invoice by ID.
     */
    public function show($id): JsonResponse
    {
        $invoice = GSTInvoice::with('merchant')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    /**
     * Store a new GST invoice.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020|max:2099',
            'merchant_id' => 'nullable|exists:merchants,id',
            'gst_provided_by' => 'nullable|string|max:255',
            'gst_payer_name' => 'required|string|max:255',
            'payer_gstin' => 'nullable|string|size:15',
            'payer_gstin_state' => 'nullable|string|max:255',
            'non_taxable_tdr' => 'nullable|numeric|min:0',
            'taxable_tdr' => 'nullable|numeric|min:0',
            'sgst' => 'nullable|numeric|min:0',
            'cgst' => 'nullable|numeric|min:0',
            'igst' => 'nullable|numeric|min:0',
            'utgst' => 'nullable|numeric|min:0',
            'invoice_value' => 'required|numeric|min:0',
            'invoice_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['invoice_number'] = GSTInvoice::generateInvoiceNumber($data['month'], $data['year']);

        $invoice = GSTInvoice::create($data);

        return response()->json([
            'success' => true,
            'message' => 'GST invoice created successfully',
            'data' => $invoice->fresh('merchant'),
        ]);
    }

    /**
     * Update a GST invoice.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $invoice = GSTInvoice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'month' => 'sometimes|required|integer|between:1,12',
            'year' => 'sometimes|required|integer|min:2020|max:2099',
            'merchant_id' => 'nullable|exists:merchants,id',
            'gst_provided_by' => 'nullable|string|max:255',
            'gst_payer_name' => 'sometimes|required|string|max:255',
            'payer_gstin' => 'nullable|string|size:15',
            'payer_gstin_state' => 'nullable|string|max:255',
            'non_taxable_tdr' => 'nullable|numeric|min:0',
            'taxable_tdr' => 'nullable|numeric|min:0',
            'sgst' => 'nullable|numeric|min:0',
            'cgst' => 'nullable|numeric|min:0',
            'igst' => 'nullable|numeric|min:0',
            'utgst' => 'nullable|numeric|min:0',
            'invoice_value' => 'sometimes|required|numeric|min:0',
            'invoice_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $invoice->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'GST invoice updated successfully',
            'data' => $invoice->fresh('merchant'),
        ]);
    }

    /**
     * Delete a GST invoice.
     */
    public function destroy($id): JsonResponse
    {
        $invoice = GSTInvoice::findOrFail($id);
        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'GST invoice deleted successfully',
        ]);
    }
}
