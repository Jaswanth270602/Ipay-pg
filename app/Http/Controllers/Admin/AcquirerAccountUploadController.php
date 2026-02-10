<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAcquirerAccountUploadJob;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AcquirerAccountUploadController extends Controller
{
    use LogsConditionally;

    /**
     * Display the upload page.
     */
    public function index(): View
    {
        $this->logInfo('Admin acquirer account upload page accessed', ['user_id' => auth()->id()]);
        return view('admin.acquirer.detail-upload');
    }

    /**
     * Get payment modes.
     */
    public function getPaymentModes(): JsonResponse
    {
        $modes = [
            'ATM Card',
            'Bank Transfer',
            'BBPS',
            'Bharat QR',
            'Bharat QR(Static)',
            'Cardless EMI',
            'Cash Card',
            'Commercial Credit Card',
            'Credit Card',
            'Debit Card',
            'Debit Pin',
            'Direct EMI',
            'E-Collect',
            'EazyPay',
            'EMI',
            'Enach',
            'International Credit Card',
            'International Debit Card',
            'Netbanking',
            'PayLater',
            'Peer to Peer',
            'Pharmarack Credit Card',
            'POS',
            'Prepaid Card',
            'UPI',
            'Wallet',
            'WhatsApp',
        ];

        return response()->json([
            'success' => true,
            'data' => $modes,
        ]);
    }

    /**
     * Get banks by payment mode.
     */
    public function getBanksByPaymentMode(Request $request): JsonResponse
    {
        $paymentMode = $request->get('payment_mode');
        
        // Comprehensive bank list with payment method associations
        $allBanks = $this->getAllBanks();
        
        // Filter banks by payment mode if specified
        if ($paymentMode) {
            $banks = array_filter($allBanks, function($bank) use ($paymentMode) {
                return in_array($paymentMode, $bank['payment_modes'] ?? []);
            });
            
            // Format bank names based on payment mode
            $banks = array_map(function($bank) use ($paymentMode) {
                // For ATM Card, append "ATM Card" to bank name if not already present
                if ($paymentMode === 'ATM Card' && strpos($bank['name'], 'ATM Card') === false) {
                    $bank['name'] = $bank['name'] . ' ATM Card';
                }
                return $bank;
            }, $banks);
        } else {
            $banks = $allBanks;
        }

        return response()->json([
            'success' => true,
            'data' => array_values($banks),
        ]);
    }

    /**
     * Get all banks with payment method associations.
     */
    private function getAllBanks(): array
    {
        // Comprehensive list of banks with their payment method associations
        $banks = [
            // Major Banks
            ['code' => 'HDFC', 'name' => 'HDFC Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Wallet', 'Bank Transfer', 'BBPS']],
            ['code' => 'ICICI', 'name' => 'ICICI Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Wallet', 'Bank Transfer', 'BBPS']],
            ['code' => 'SBI', 'name' => 'State Bank of India', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Wallet', 'Bank Transfer', 'BBPS']],
            ['code' => 'AXIS', 'name' => 'Axis Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Wallet', 'Bank Transfer', 'BBPS']],
            ['code' => 'KOTAK', 'name' => 'Kotak Mahindra Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Wallet', 'Bank Transfer']],
            ['code' => 'YES', 'name' => 'Yes Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Wallet', 'Bank Transfer']],
            ['code' => 'PNB', 'name' => 'Punjab National Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer', 'BBPS']],
            ['code' => 'BOB', 'name' => 'Bank of Baroda', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer', 'BBPS']],
            ['code' => 'BOI', 'name' => 'Bank of India', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer', 'BBPS']],
            ['code' => 'UBI', 'name' => 'Union Bank of India', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer', 'BBPS']],
            ['code' => 'CAN', 'name' => 'Canara Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer', 'BBPS']],
            ['code' => 'BOM', 'name' => 'Bank of Maharashtra', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'IOB', 'name' => 'Indian Overseas Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'IDBI', 'name' => 'IDBI Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'FEDERAL', 'name' => 'Federal Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'RBL', 'name' => 'RBL Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Wallet', 'Bank Transfer']],
            ['code' => 'INDB', 'name' => 'IndusInd Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Wallet', 'Bank Transfer']],
            ['code' => 'IDFC', 'name' => 'IDFC First Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Wallet', 'Bank Transfer']],
            ['code' => 'SOUTH', 'name' => 'South Indian Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'KARUR', 'name' => 'Karur Vysya Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'CUB', 'name' => 'City Union Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'DCB', 'name' => 'DCB Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'TMB', 'name' => 'Tamilnad Mercantile Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'JKB', 'name' => 'Jammu & Kashmir Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'LVB', 'name' => 'Laxmi Vilas Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'BMB', 'name' => 'Bharatiya Mahila Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'KGB', 'name' => 'Kerala Gramin Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'PKGB', 'name' => 'Pragathi Krishna Gramin Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'RSCB', 'name' => 'Rajasthan State Coop Bank', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
            ['code' => 'SMCB', 'name' => 'Shivalik Mercantile Co-Op Bank Limited', 'payment_modes' => ['ATM Card', 'Debit Card', 'Credit Card', 'Netbanking', 'UPI', 'Bank Transfer']],
        ];

        return $banks;
    }

    /**
     * Upload acquirer account file.
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
                'payment_mode' => 'nullable|string',
                'bank_codes' => 'nullable|array',
            ]);

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('acquirer_account_uploads', $fileName, 'local');

            // Create job record
            $jobId = DB::table('acquirer_account_upload_jobs')->insertGetId([
                'job_name' => 'Acquirer Account Upload - ' . $fileName,
                'file_path' => $filePath,
                'payment_mode' => $request->get('payment_mode'),
                'bank_codes' => json_encode($request->get('bank_codes', [])),
                'status' => 'pending',
                'progress' => 0,
                'started_at' => null,
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Queue job to process file
            ProcessAcquirerAccountUploadJob::dispatch($jobId, $filePath);

            $this->logInfo('Acquirer account upload job created', [
                'user_id' => auth()->id(),
                'job_id' => $jobId,
                'file' => $fileName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully. Processing started.',
                'job_id' => $jobId,
            ]);
        } catch (\Exception $e) {
            $this->logError('Failed to upload acquirer account file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upload jobs.
     */
    public function getJobs(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 5), 50);
            
            $query = DB::table('acquirer_account_upload_jobs')->latest();

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

            // Date filters
            if ($request->has('filter_started_from') && $request->get('filter_started_from')) {
                $query->whereDate('started_at', '>=', $request->get('filter_started_from'));
            }
            if ($request->has('filter_started_to') && $request->get('filter_started_to')) {
                $query->whereDate('started_at', '<=', $request->get('filter_started_to'));
            }
            if ($request->has('filter_finished_from') && $request->get('filter_finished_from')) {
                $query->whereDate('finished_at', '>=', $request->get('filter_finished_from'));
            }
            if ($request->has('filter_finished_to') && $request->get('filter_finished_to')) {
                $query->whereDate('finished_at', '<=', $request->get('filter_finished_to'));
            }

            $jobs = $query->paginate($perPage);

            $data = collect($jobs->items())->map(function($job) {
                return [
                    'id' => $job->id,
                    'job_id' => $job->id,
                    'job_name' => $job->job_name ?? '-',
                    'progress' => $job->progress ?? 0,
                    'status' => $job->status ?? 'pending',
                    'export_file_path' => $job->export_file_path ?? '-',
                    'started_at' => $job->started_at ? date('Y-m-d H:i:s', strtotime($job->started_at)) : '-',
                    'finished_at' => $job->finished_at ? date('Y-m-d H:i:s', strtotime($job->finished_at)) : '-',
                    'error' => $job->error ?? '-',
                    'status_info' => $job->status_info ?? '-',
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
                'message' => 'Failed to fetch jobs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download status file.
     */
    public function downloadStatusFile($id): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $job = DB::table('acquirer_account_upload_jobs')->where('id', $id)->first();
        
        if (!$job) {
            abort(404, 'Job not found');
        }

        // If export file exists, download it
        if ($job->export_file_path && Storage::disk('local')->exists($job->export_file_path)) {
            return Storage::download($job->export_file_path);
        }

        // Otherwise, generate a status file on-the-fly
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="acquirer_upload_job_' . $id . '_status.csv"',
        ];

        $callback = function() use ($job) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ACQUIRER ACCOUNT UPLOAD JOB DETAILS']);
            fputcsv($file, []);
            
            fputcsv($file, ['Job Id', $job->id ?? 'N/A']);
            fputcsv($file, ['Job Name', $job->job_name ?? 'N/A']);
            fputcsv($file, ['Progress', ($job->progress ?? 0) . '%']);
            fputcsv($file, ['Status', strtoupper($job->status ?? 'N/A')]);
            fputcsv($file, ['Started At', $job->started_at ? date('Y-m-d H:i:s', strtotime($job->started_at)) : 'N/A']);
            fputcsv($file, ['Finished At', $job->finished_at ? date('Y-m-d H:i:s', strtotime($job->finished_at)) : 'N/A']);
            fputcsv($file, ['Error', $job->error ?? 'None']);
            fputcsv($file, ['Status Info', $job->status_info ?? 'N/A']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download template file.
     */
    public function downloadTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="acquirer_account_upload_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'acquirer name',
                'account id',
                'secret key',
                'salt',
                'additional key1',
                'additional key2',
                'additional key3',
                'additional keys',
                'description',
                'white list url',
                'mode',
                'sector',
                'nodal account',
                'live request url',
                'live query url',
                'live refund url',
                'test request url',
                'test query url',
                'test refund url',
                'hdfc me code',
                'is refund allowed',
                'is settlement allowed',
                'email ids',
                'reference account id for duplicating account detail',
                'merchant ids',
                'rate',
                'type',
                'owner team name'
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
