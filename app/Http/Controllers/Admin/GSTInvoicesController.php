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

            if ($request->filled('non_taxable_tdr')) {
                $query->where('non_taxable_tdr', $request->get('non_taxable_tdr'));
            }
            if ($request->filled('taxable_tdr')) {
                $query->where('taxable_tdr', $request->get('taxable_tdr'));
            }
            if ($request->filled('sgst')) {
                $query->where('sgst', $request->get('sgst'));
            }
            if ($request->filled('cgst')) {
                $query->where('cgst', $request->get('cgst'));
            }
            if ($request->filled('igst')) {
                $query->where('igst', $request->get('igst'));
            }
            if ($request->filled('utgst')) {
                $query->where('utgst', $request->get('utgst'));
            }

            // Amount filters
            if ($request->filled('invoice_value')) {
                $query->where('invoice_value', $request->get('invoice_value'));
            }
            if ($request->filled('invoice_value_min')) {
                $query->where('invoice_value', '>=', $request->get('invoice_value_min'));
            }

            if ($request->filled('invoice_value_max')) {
                $query->where('invoice_value', '<=', $request->get('invoice_value_max'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            $allowedSortColumns = [
                'id', 'invoice_number', 'month', 'year', 'merchant_id', 'gst_provided_by',
                'gst_payer_name', 'payer_gstin', 'payer_gstin_state', 'non_taxable_tdr',
                'taxable_tdr', 'sgst', 'cgst', 'igst', 'utgst', 'invoice_value',
                'invoice_date', 'created_at', 'updated_at',
            ];
            if (!in_array($sortBy, $allowedSortColumns, true)) {
                $sortBy = 'id';
            }
            $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
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
        $merchants = Merchant::select('id', 'business_name', 'name', 'merchant_id')
            ->orderByRaw('COALESCE(business_name, name) asc')
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
        $payload = $request->all();
        foreach (['gst_provided_by', 'gst_payer_name', 'payer_gstin', 'payer_gstin_state', 'notes'] as $field) {
            if (array_key_exists($field, $payload) && is_string($payload[$field])) {
                $payload[$field] = trim($payload[$field]);
            }
        }
        if (!empty($payload['payer_gstin']) && is_string($payload['payer_gstin'])) {
            $payload['payer_gstin'] = strtoupper($payload['payer_gstin']);
        }

        $validator = Validator::make($payload, [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020|max:2099',
            'merchant_id' => 'nullable|exists:merchants,id',
            'gst_provided_by' => 'nullable|string|max:255',
            'gst_payer_name' => 'required|string|max:255',
            'payer_gstin' => 'nullable|string|size:15|regex:/^[A-Z0-9]{15}$/',
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
        ], [
            'month.required' => 'Month is required.',
            'year.required' => 'Year is required.',
            'gst_payer_name.required' => 'GST payer name is required.',
            'payer_gstin.size' => 'Payer GSTIN must be exactly 15 characters.',
            'payer_gstin.regex' => 'Payer GSTIN must contain only uppercase letters and numbers.',
            'invoice_value.required' => 'Invoice value is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        foreach (['non_taxable_tdr', 'taxable_tdr', 'sgst', 'cgst', 'igst', 'utgst'] as $n) {
            if (!array_key_exists($n, $data) || $data[$n] === null || $data[$n] === '') {
                $data[$n] = 0;
            }
        }
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

        $payload = $request->all();
        foreach (['gst_provided_by', 'gst_payer_name', 'payer_gstin', 'payer_gstin_state', 'notes'] as $field) {
            if (array_key_exists($field, $payload) && is_string($payload[$field])) {
                $payload[$field] = trim($payload[$field]);
            }
        }
        if (!empty($payload['payer_gstin']) && is_string($payload['payer_gstin'])) {
            $payload['payer_gstin'] = strtoupper($payload['payer_gstin']);
        }

        $validator = Validator::make($payload, [
            'month' => 'sometimes|required|integer|between:1,12',
            'year' => 'sometimes|required|integer|min:2020|max:2099',
            'merchant_id' => 'nullable|exists:merchants,id',
            'gst_provided_by' => 'nullable|string|max:255',
            'gst_payer_name' => 'sometimes|required|string|max:255',
            'payer_gstin' => 'nullable|string|size:15|regex:/^[A-Z0-9]{15}$/',
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
        ], [
            'gst_payer_name.required' => 'GST payer name is required.',
            'payer_gstin.size' => 'Payer GSTIN must be exactly 15 characters.',
            'payer_gstin.regex' => 'Payer GSTIN must contain only uppercase letters and numbers.',
            'invoice_value.required' => 'Invoice value is required.',
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
