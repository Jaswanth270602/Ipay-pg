<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatatableExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DatatableExportController extends Controller
{
    /**
     * Display the Datatable Exports page.
     */
    public function index(): View
    {
        return view('admin.reports.datatable-exports.index');
    }

    /**
     * Get Datatable Export data with filters and pagination.
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->integer('per_page', 5), 50);

            $query = DatatableExport::query();

            // Date Created filter
            if ($request->filled('date_created')) {
                $query->whereDate('date_created', $request->get('date_created'));
            }

            // Page Category filter
            if ($request->filled('page_category')) {
                $query->where('page_category', 'like', '%' . $request->get('page_category') . '%');
            }

            // Queue Status filter
            if ($request->filled('queue_status') && $request->get('queue_status') !== 'all') {
                $query->where('queue_status', $request->get('queue_status'));
            }

            // File Type filter
            if ($request->filled('file_type') && $request->get('file_type') !== 'all') {
                $query->where('file_type', $request->get('file_type'));
            }

            // Downloadable URL filter
            if ($request->filled('downloadable_url')) {
                $query->where('downloadable_url', 'like', '%' . $request->get('downloadable_url') . '%');
            }

            // Time For Expiry filter (search by expiry_time)
            if ($request->filled('time_for_expiry')) {
                $query->whereDate('expiry_time', $request->get('time_for_expiry'));
            }

            // File Name filter
            if ($request->filled('file_name')) {
                $query->where('file_name', 'like', '%' . $request->get('file_name') . '%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $exports = $query->paginate($perPage);

            // Transform data to include computed fields
            $data = $exports->map(function ($export) {
                return [
                    'id' => $export->id,
                    'date_created' => $export->date_created ? $export->date_created->format('Y-m-d H:i:s') : null,
                    'page_category' => $export->page_category ?? 'N/A',
                    'queue_status' => $export->queue_status,
                    'file_type' => $export->file_type,
                    'downloadable_url' => $export->downloadable_url ?? 'N/A',
                    'time_for_expiry' => $export->getTimeForExpiry(),
                    'file_name' => $export->file_name ?? 'N/A',
                    'expiry_time' => $export->expiry_time ? $export->expiry_time->format('Y-m-d H:i:s') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data->toArray(),
                'pagination' => [
                    'current_page' => $exports->currentPage(),
                    'per_page' => $exports->perPage(),
                    'total' => $exports->total(),
                    'last_page' => $exports->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch datatable exports: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Get queue statuses for dropdown.
     */
    public function getQueueStatuses(): JsonResponse
    {
        try {
            $statuses = ['pending', 'processing', 'completed', 'failed'];
            return response()->json([
                'success' => true,
                'data' => $statuses,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch queue statuses: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get file types for dropdown.
     */
    public function getFileTypes(): JsonResponse
    {
        try {
            $types = ['csv', 'xlsx', 'pdf', 'json'];
            return response()->json([
                'success' => true,
                'data' => $types,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch file types: ' . $e->getMessage(),
            ], 500);
        }
    }
}
