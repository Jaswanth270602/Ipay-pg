<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdhocReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdhocReportController extends Controller
{
    /**
     * Display the Adhoc Reports page.
     */
    public function index(): View
    {
        return view('admin.reports.miscellaneous.index');
    }

    /**
     * Get Adhoc Report data with filters and pagination.
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->integer('per_page', 5), 50);

            $query = AdhocReport::query();

            // Filters
            if ($request->filled('adhoc_report_id')) {
                $query->where('id', $request->get('adhoc_report_id'));
            }

            if ($request->filled('adhoc_report_name')) {
                $query->where('adhoc_report_name', 'like', '%' . $request->get('adhoc_report_name') . '%');
            }

            if ($request->filled('adhoc_report_description')) {
                $query->where('adhoc_report_description', 'like', '%' . $request->get('adhoc_report_description') . '%');
            }

            if ($request->filled('adhoc_report_created_date')) {
                $dateRange = $request->get('adhoc_report_created_date');
                if (strpos($dateRange, '-') !== false) {
                    $dates = explode('-', $dateRange);
                    if (count($dates) === 2) {
                        $startDate = trim($dates[0]);
                        $endDate = trim($dates[1]);
                        if (!empty($startDate) && !empty($endDate)) {
                            $query->whereBetween('adhoc_report_created_date', [
                                date('Y-m-d 00:00:00', strtotime($startDate)),
                                date('Y-m-d 23:59:59', strtotime($endDate))
                            ]);
                        }
                    }
                } else {
                    $query->whereDate('adhoc_report_created_date', $dateRange);
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $reports = $query->paginate($perPage);

            // Transform data
            $data = $reports->map(function ($report) {
                return [
                    'id' => $report->id,
                    'adhoc_report_id' => $report->id,
                    'adhoc_report_name' => $report->adhoc_report_name,
                    'adhoc_report_description' => $report->adhoc_report_description ?? 'N/A',
                    'adhoc_report_created_date' => $report->adhoc_report_created_date ? $report->adhoc_report_created_date->format('Y-m-d H:i:s') : null,
                    'sql_query' => $report->sql_query,
                    'is_active' => $report->is_active,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data->toArray(),
                'pagination' => [
                    'current_page' => $reports->currentPage(),
                    'per_page' => $reports->perPage(),
                    'total' => $reports->total(),
                    'last_page' => $reports->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch adhoc reports: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Store a new adhoc report.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'adhoc_report_name' => 'required|string|max:255',
                'adhoc_report_description' => 'nullable|string',
                'sql_query' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $report = AdhocReport::create([
                'adhoc_report_name' => $request->input('adhoc_report_name'),
                'adhoc_report_description' => $request->input('adhoc_report_description'),
                'sql_query' => $request->input('sql_query'),
                'adhoc_report_created_date' => now(),
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Adhoc report created successfully',
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create adhoc report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single adhoc report.
     */
    public function show($id): JsonResponse
    {
        try {
            $report = AdhocReport::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $report->id,
                    'adhoc_report_name' => $report->adhoc_report_name,
                    'adhoc_report_description' => $report->adhoc_report_description,
                    'sql_query' => $report->sql_query,
                    'adhoc_report_created_date' => $report->adhoc_report_created_date ? $report->adhoc_report_created_date->format('Y-m-d H:i:s') : null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch adhoc report: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update an adhoc report.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'adhoc_report_name' => 'required|string|max:255',
                'adhoc_report_description' => 'nullable|string',
                'sql_query' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $report = AdhocReport::findOrFail($id);
            $report->update([
                'adhoc_report_name' => $request->input('adhoc_report_name'),
                'adhoc_report_description' => $request->input('adhoc_report_description'),
                'sql_query' => $request->input('sql_query'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Adhoc report updated successfully',
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update adhoc report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an adhoc report.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $report = AdhocReport::findOrFail($id);
            $report->delete();

            return response()->json([
                'success' => true,
                'message' => 'Adhoc report deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete adhoc report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate an adhoc report.
     */
    public function duplicate($id): JsonResponse
    {
        try {
            $original = AdhocReport::findOrFail($id);
            
            $duplicate = AdhocReport::create([
                'adhoc_report_name' => $original->adhoc_report_name . ' (Copy)',
                'adhoc_report_description' => $original->adhoc_report_description,
                'sql_query' => $original->sql_query,
                'adhoc_report_created_date' => now(),
                'created_by' => auth()->id(),
                'is_active' => $original->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Adhoc report duplicated successfully',
                'data' => $duplicate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate adhoc report: ' . $e->getMessage(),
            ], 500);
        }
    }
}
