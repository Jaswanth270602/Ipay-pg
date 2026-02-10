<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessBulkRefundUpdateJob;

class BulkRefundUpdateController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin bulk refund update page accessed', ['user_id' => auth()->id()]);
        return view('admin.payments.bulk-refund-update');
    }

    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
            ]);

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('bulk_refunds', $fileName, 'local');

            // Process file and create job record
            $job = DB::table('bulk_refund_jobs')->insertGetId([
                'job_name' => 'Bulk Refund Update - ' . $fileName,
                'file_path' => $filePath,
                'status' => 'pending',
                'progress' => 0,
                'started_at' => null,
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Queue job to process file
            ProcessBulkRefundUpdateJob::dispatch($job, $filePath);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully. Processing started.',
                'job_id' => $job,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getJobs(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = DB::table('bulk_refund_jobs')->latest();

            // Filters
            if ($request->has('filter_job_id') && $request->get('filter_job_id')) {
                $query->where('id', 'like', "%{$request->get('filter_job_id')}%");
            }
            if ($request->has('filter_job_name') && $request->get('filter_job_name')) {
                $query->where('job_name', 'like', "%{$request->get('filter_job_name')}%");
            }
            if ($request->has('filter_status') && $request->get('filter_status') !== 'all') {
                $query->where('status', $request->get('filter_status'));
            }

            $jobs = $query->paginate($perPage);

            $data = collect($jobs->items())->map(function($job) {
                return [
                    'id' => $job->id,
                    'job_id' => $job->id,
                    'job_name' => $job->job_name ?? '-',
                    'progress' => $job->progress ?? 0,
                    'status' => $job->status ?? 'pending',
                    'export_files' => $job->export_file_path ?? '-',
                    'started_at' => $job->started_at ? date('Y-m-d H:i:s', strtotime($job->started_at)) : '-',
                    'finished_at' => $job->finished_at ? date('Y-m-d H:i:s', strtotime($job->finished_at)) : '-',
                    'error' => $job->error ?? '-',
                    'status_info' => $job->status_info ?? '-',
                    'user_name' => 'Admin',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $jobs->currentPage(),
                    'per_page' => $jobs->perPage(),
                    'total' => $jobs->total(),
                    'last_page' => $jobs->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch jobs',
            ], 500);
        }
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bulk_refund_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            // CSV Headers matching the grid columns
            fputcsv($file, [
                'Refund ID',
                'Merchant ID',
                'Merchant Name',
                'Payment ID',
                'Customer IP',
                'Transaction Sequence ID',
                'Transaction ID',
                'Order ID',
                'Payer Name',
                'Payer Email',
                'Payer Phone',
                'Refund Status',
                'Refund Description',
                'Refund Amount',
                'Refund Charges',
                'Refund Tax On Charges',
                'Transaction Amount',
                'Refund Request Date',
                'Refund Initiated Date',
                'Refund Reference No',
                'Is Refund Approved',
                'Refund PG Completed',
                'Latest API Response'
            ]);
            
            // Add sample entries
            fputcsv($file, [
                'RFD_123456789012',
                '1',
                'Sample Merchant',
                'TXN_1234567890',
                '192.168.1.1',
                '1001',
                'TXN_1234567890',
                'ORD_1234567890',
                'John Doe',
                'john@example.com',
                '9876543210',
                'pending',
                'Customer requested refund',
                '100.00',
                '0.00',
                '0.00',
                '100.00',
                '2025-12-09 10:00:00',
                '',
                '',
                'No',
                'No',
                '{}'
            ]);
            
            fputcsv($file, [
                'RFD_123456789013',
                '2',
                'Another Merchant',
                'TXN_1234567891',
                '192.168.1.2',
                '1002',
                'TXN_1234567891',
                'ORD_1234567891',
                'Jane Smith',
                'jane@example.com',
                '9876543211',
                'completed',
                'Product not delivered',
                '250.50',
                '2.50',
                '0.45',
                '250.50',
                '2025-12-08 14:30:00',
                '2025-12-08 15:00:00',
                'REF_1234567890',
                'Yes',
                'Yes',
                '{"status":"success","refund_id":"REF_1234567890"}'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadStatusFile($id): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $job = DB::table('bulk_refund_jobs')->where('id', $id)->first();
        
        if (!$job) {
            abort(404, 'Job not found');
        }

        // If export file exists, download it
        if ($job->export_file_path && Storage::disk('local')->exists($job->export_file_path)) {
            return Storage::download($job->export_file_path);
        }

        // Otherwise, generate a status file on-the-fly with current job information
        $user = DB::table('users')->where('id', $job->user_id ?? null)->first();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bulk_refund_job_' . $id . '_status.csv"',
        ];

        $callback = function() use ($job, $user) {
            $file = fopen('php://output', 'w');
            
            // Write section header
            fputcsv($file, ['BULK REFUND UPDATE JOB DETAILS']);
            fputcsv($file, []);
            
            // Write job information with all columns
            fputcsv($file, ['Job Id', $job->id ?? 'N/A']);
            fputcsv($file, ['Job Name', $job->job_name ?? 'N/A']);
            fputcsv($file, ['Progress', ($job->progress ?? 0) . '%']);
            fputcsv($file, ['Status', strtoupper($job->status ?? 'N/A')]);
            fputcsv($file, ['Started At', $job->started_at ? date('Y-m-d H:i:s', strtotime($job->started_at)) : 'N/A']);
            fputcsv($file, ['Finished At', $job->finished_at ? date('Y-m-d H:i:s', strtotime($job->finished_at)) : 'N/A']);
            fputcsv($file, ['Error', $job->error ?? 'None']);
            fputcsv($file, ['Status Info', $job->status_info ?? 'N/A']);
            fputcsv($file, ['User Name', $user->name ?? 'Admin']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
