<?php

namespace App\Jobs;

use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessBulkRefundUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobId;
    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(int $jobId, string $filePath)
    {
        $this->jobId = $jobId;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update job status to processing
            DB::table('bulk_refund_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => 'processing',
                    'started_at' => now(),
                ]);

            $fullPath = Storage::disk('local')->path($this->filePath);
            
            // Check if file exists
            if (!file_exists($fullPath)) {
                throw new \Exception("File not found: {$fullPath}");
            }

            // Read CSV file
            $handle = fopen($fullPath, 'r');
            if (!$handle) {
                throw new \Exception("Cannot open file: {$fullPath}");
            }

            // Read header row
            $headers = fgetcsv($handle);
            if (!$headers) {
                throw new \Exception("Invalid CSV file: No headers found");
            }

            // Normalize headers (trim and lowercase)
            $headers = array_map(function($h) {
                return strtolower(trim($h));
            }, $headers);

            // Expected columns
            $expectedColumns = ['refund id', 'status', 'notes', 'reason', 'amount', 'currency'];
            $columnIndexes = [];
            foreach ($expectedColumns as $col) {
                $index = array_search($col, $headers);
                if ($index !== false) {
                    $columnIndexes[$col] = $index;
                }
            }

            // Refund ID is required
            if (!isset($columnIndexes['refund id'])) {
                throw new \Exception("Required column 'Refund ID' not found in CSV");
            }

            $results = [];
            $rowNumber = 1; // Start from 1 (header is row 0)
            $successCount = 0;
            $errorCount = 0;
            $totalRows = 0;

            // Process each row
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $totalRows++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Get refund ID (required)
                $refundId = trim($row[$columnIndexes['refund id']] ?? '');
                
                if (empty($refundId)) {
                    $results[] = [
                        'row' => $rowNumber,
                        'refund_id' => '',
                        'status' => 'error',
                        'message' => 'Refund ID is required',
                    ];
                    $errorCount++;
                    continue;
                }

                // Find refund
                $refund = Refund::where('refund_id', $refundId)->first();
                
                if (!$refund) {
                    $results[] = [
                        'row' => $rowNumber,
                        'refund_id' => $refundId,
                        'status' => 'error',
                        'message' => "Refund not found: {$refundId}",
                    ];
                    $errorCount++;
                    continue;
                }

                // Prepare update data
                $updateData = [];

                // Update Status (if provided)
                if (isset($columnIndexes['status'])) {
                    $status = trim($row[$columnIndexes['status']] ?? '');
                    if (!empty($status)) {
                        $validStatuses = ['pending', 'processing', 'completed', 'failed', 'cancelled'];
                        if (in_array(strtolower($status), $validStatuses)) {
                            $updateData['status'] = strtolower($status);
                            
                            // If status is completed, set processed_at
                            if (strtolower($status) === 'completed' && !$refund->processed_at) {
                                $updateData['processed_at'] = now();
                            }
                        } else {
                            $results[] = [
                                'row' => $rowNumber,
                                'refund_id' => $refundId,
                                'status' => 'error',
                                'message' => "Invalid status: {$status}. Must be one of: " . implode(', ', $validStatuses),
                            ];
                            $errorCount++;
                            continue;
                        }
                    }
                }

                // Update Notes (if provided)
                if (isset($columnIndexes['notes'])) {
                    $notes = trim($row[$columnIndexes['notes']] ?? '');
                    if (!empty($notes)) {
                        $updateData['notes'] = $notes;
                    }
                }

                // Update Reason (if provided)
                if (isset($columnIndexes['reason'])) {
                    $reason = trim($row[$columnIndexes['reason']] ?? '');
                    if (!empty($reason)) {
                        $updateData['reason'] = $reason;
                    }
                }

                // Update Amount (if provided)
                if (isset($columnIndexes['amount'])) {
                    $amount = trim($row[$columnIndexes['amount']] ?? '');
                    if (!empty($amount)) {
                        $amount = filter_var($amount, FILTER_VALIDATE_FLOAT);
                        if ($amount !== false && $amount > 0) {
                            $updateData['amount'] = $amount;
                        } else {
                            $results[] = [
                                'row' => $rowNumber,
                                'refund_id' => $refundId,
                                'status' => 'error',
                                'message' => "Invalid amount: {$row[$columnIndexes['amount']]}",
                            ];
                            $errorCount++;
                            continue;
                        }
                    }
                }

                // Update Currency (if provided)
                if (isset($columnIndexes['currency'])) {
                    $currency = trim($row[$columnIndexes['currency']] ?? '');
                    if (!empty($currency)) {
                        if (strlen($currency) === 3) {
                            $updateData['currency'] = strtoupper($currency);
                        } else {
                            $results[] = [
                                'row' => $rowNumber,
                                'refund_id' => $refundId,
                                'status' => 'error',
                                'message' => "Invalid currency: {$currency}. Must be 3 characters (e.g., USD)",
                            ];
                            $errorCount++;
                            continue;
                        }
                    }
                }

                // Update refund if we have data to update
                if (!empty($updateData)) {
                    try {
                        $refund->update($updateData);
                        $results[] = [
                            'row' => $rowNumber,
                            'refund_id' => $refundId,
                            'status' => 'success',
                            'message' => 'Refund updated successfully',
                        ];
                        $successCount++;
                    } catch (\Exception $e) {
                        $results[] = [
                            'row' => $rowNumber,
                            'refund_id' => $refundId,
                            'status' => 'error',
                            'message' => 'Update failed: ' . $e->getMessage(),
                        ];
                        $errorCount++;
                    }
                } else {
                    $results[] = [
                        'row' => $rowNumber,
                        'refund_id' => $refundId,
                        'status' => 'skipped',
                        'message' => 'No data to update',
                    ];
                }

                // Update progress
                $progress = (int)(($rowNumber / max($totalRows, 1)) * 100);
                DB::table('bulk_refund_jobs')
                    ->where('id', $this->jobId)
                    ->update(['progress' => min($progress, 100)]);
            }

            fclose($handle);

            // Generate status report CSV
            $exportFileName = 'bulk_refund_status_' . $this->jobId . '_' . time() . '.csv';
            $exportPath = 'bulk_refunds/export/' . $exportFileName;
            $exportFullPath = Storage::disk('local')->path($exportPath);
            
            // Create export directory if it doesn't exist
            $exportDir = dirname($exportFullPath);
            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0755, true);
            }

            // Get fresh job information
            $job = DB::table('bulk_refund_jobs')->where('id', $this->jobId)->first();
            $user = DB::table('users')->where('id', $job->user_id ?? null)->first();

            $exportHandle = fopen($exportFullPath, 'w');
            if ($exportHandle) {
                // Write section header
                fputcsv($exportHandle, ['BULK REFUND UPDATE JOB DETAILS']);
                fputcsv($exportHandle, []);
                
                // Write job information with all columns in a clear format
                fputcsv($exportHandle, ['Job Id', $job->id ?? 'N/A']);
                fputcsv($exportHandle, ['Job Name', $job->job_name ?? 'N/A']);
                fputcsv($exportHandle, ['Progress', ($job->progress ?? 0) . '%']);
                fputcsv($exportHandle, ['Status', strtoupper($job->status ?? 'N/A')]);
                fputcsv($exportHandle, ['Started At', $job->started_at ? date('Y-m-d H:i:s', strtotime($job->started_at)) : 'N/A']);
                fputcsv($exportHandle, ['Finished At', $job->finished_at ? date('Y-m-d H:i:s', strtotime($job->finished_at)) : 'N/A']);
                fputcsv($exportHandle, ['Error', $job->error ?? 'None']);
                fputcsv($exportHandle, ['Status Info', $job->status_info ?? 'N/A']);
                fputcsv($exportHandle, ['User Name', $user->name ?? 'Admin']);
                
                // Empty row separator
                fputcsv($exportHandle, []);
                fputcsv($exportHandle, []);
                
                // Write summary section
                fputcsv($exportHandle, ['PROCESSING SUMMARY']);
                fputcsv($exportHandle, ['Total Rows Processed', $totalRows]);
                fputcsv($exportHandle, ['Successful Updates', $successCount]);
                fputcsv($exportHandle, ['Errors', $errorCount]);
                fputcsv($exportHandle, ['Skipped', count($results) - $successCount - $errorCount]);
                
                // Empty row separator
                fputcsv($exportHandle, []);
                fputcsv($exportHandle, []);
                
                // Write detailed results header
                fputcsv($exportHandle, ['DETAILED PROCESSING RESULTS']);
                fputcsv($exportHandle, ['Row', 'Refund ID', 'Status', 'Message']);
                
                // Write results
                foreach ($results as $result) {
                    fputcsv($exportHandle, [
                        $result['row'],
                        $result['refund_id'],
                        strtoupper($result['status']),
                        $result['message'],
                    ]);
                }
                fclose($exportHandle);
            }

            // Update job status
            $finalStatus = $errorCount > 0 && $successCount === 0 ? 'failed' : 'completed';
            $statusInfo = "Processed: {$totalRows} rows | Success: {$successCount} | Errors: {$errorCount}";

            DB::table('bulk_refund_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => $finalStatus,
                    'progress' => 100,
                    'finished_at' => now(),
                    'export_file_path' => $exportPath,
                    'status_info' => $statusInfo,
                ]);

            Log::info("Bulk refund update job {$this->jobId} completed", [
                'total_rows' => $totalRows,
                'success_count' => $successCount,
                'error_count' => $errorCount,
            ]);

        } catch (\Exception $e) {
            // Update job status to failed
            DB::table('bulk_refund_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => 'failed',
                    'progress' => 0,
                    'finished_at' => now(),
                    'error' => $e->getMessage(),
                ]);

            Log::error("Bulk refund update job {$this->jobId} failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

