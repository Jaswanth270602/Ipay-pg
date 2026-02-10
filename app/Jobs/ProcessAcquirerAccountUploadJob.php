<?php

namespace App\Jobs;

use App\Models\AcquirerAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessAcquirerAccountUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $jobId;
    public $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($jobId, $filePath)
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
            DB::table('acquirer_account_upload_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => 'processing',
                    'started_at' => now(),
                    'progress' => 10,
                ]);

            // Read CSV file
            $filePath = Storage::disk('local')->path($this->filePath);
            
            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $this->filePath);
            }

            $file = fopen($filePath, 'r');
            $headers = fgetcsv($file); // Read header row
            
            if (!$headers) {
                throw new \Exception('Invalid CSV file: No headers found');
            }

            // Normalize headers (lowercase, trim, replace spaces with underscores)
            $headers = array_map(function($header) {
                return strtolower(trim(str_replace(' ', '_', $header)));
            }, $headers);

            $processed = 0;
            $failed = 0;
            $errors = [];

            // Process each row
            while (($row = fgetcsv($file)) !== false) {
                if (count($row) < count($headers)) {
                    continue; // Skip incomplete rows
                }

                try {
                    $data = array_combine($headers, $row);
                    
                    // Map CSV columns to database fields
                    $accountData = [
                        'account_id' => $data['account_id'] ?? null,
                        'acquirer_name' => $data['acquirer_name'] ?? null,
                        'secret_key' => $data['secret_key'] ?? null,
                        'salt' => $data['salt'] ?? null,
                        'additional_key_1' => $data['additional_key1'] ?? $data['additional_key_1'] ?? null,
                        'additional_key_2' => $data['additional_key2'] ?? $data['additional_key_2'] ?? null,
                        'additional_key_3' => $data['additional_key3'] ?? $data['additional_key_3'] ?? null,
                        'additional_key_data' => $data['additional_keys'] ?? null,
                        'description' => $data['description'] ?? null,
                        'whitelist_url' => $data['white_list_url'] ?? $data['whitelist_url'] ?? null,
                        'mode' => strtoupper($data['mode'] ?? 'TEST'),
                        'sector' => $data['sector'] ?? null,
                        'nodal_account' => $data['nodal_account'] ?? null,
                        'live_request_url' => $data['live_request_url'] ?? null,
                        'live_query_url' => $data['live_query_url'] ?? null,
                        'live_refund_url' => $data['live_refund_url'] ?? null,
                        'test_request_url' => $data['test_request_url'] ?? null,
                        'test_query_url' => $data['test_query_url'] ?? null,
                        'test_refund_url' => $data['test_refund_url'] ?? null,
                        'hdfc_me_code' => $data['hdfc_me_code'] ?? null,
                        'refund_allowed' => $this->parseBoolean($data['is_refund_allowed'] ?? 'true'),
                        'settlements_to_be_created' => $this->parseBoolean($data['is_settlement_allowed'] ?? 'true'),
                        'email_ids' => $data['email_ids'] ?? null,
                        'team' => $data['owner_team_name'] ?? null,
                        'is_active' => true,
                    ];

                    // Validate required fields
                    if (empty($accountData['account_id']) || empty($accountData['acquirer_name'])) {
                        throw new \Exception('Missing required fields: account_id or acquirer_name');
                    }

                    // Check if account already exists
                    $existing = AcquirerAccount::where('account_id', $accountData['account_id'])->first();
                    
                    if ($existing) {
                        // Update existing account
                        $existing->update($accountData);
                    } else {
                        // Create new account
                        AcquirerAccount::create($accountData);
                    }

                    $processed++;

                    // Update progress
                    $progress = min(10 + (($processed / 100) * 80), 90);
                    DB::table('acquirer_account_upload_jobs')
                        ->where('id', $this->jobId)
                        ->update(['progress' => (int)$progress]);

                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = 'Row ' . ($processed + $failed) . ': ' . $e->getMessage();
                    
                    if (count($errors) > 100) {
                        $errors[] = '... and more errors (truncated)';
                        break;
                    }
                }
            }

            fclose($file);

            // Mark job as completed
            DB::table('acquirer_account_upload_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => 'completed',
                    'progress' => 100,
                    'finished_at' => now(),
                    'status_info' => "Processed: {$processed}, Failed: {$failed}",
                    'error' => $failed > 0 ? implode("\n", array_slice($errors, 0, 10)) : null,
                ]);

        } catch (\Exception $e) {
            Log::error('Acquirer account upload job failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            DB::table('acquirer_account_upload_jobs')
                ->where('id', $this->jobId)
                ->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'error' => $e->getMessage(),
                ]);
        }
    }

    /**
     * Parse boolean value from string.
     */
    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        $value = strtolower(trim($value));
        return in_array($value, ['true', '1', 'yes', 'y', 'on']);
    }
}
