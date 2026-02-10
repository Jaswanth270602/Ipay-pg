<?php

namespace App\Console\Commands;

use App\Services\SettlementEngine;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessDailySettlements extends Command
{
    protected $signature = 'settlements:process-daily 
                            {--date= : Process settlements for specific date (YYYY-MM-DD)}
                            {--merchant= : Process for specific merchant ID}
                            {--dry-run : Show what would happen without actually processing}';

    protected $description = 'Process daily settlements for merchants';

    protected SettlementEngine $engine;

    public function __construct(SettlementEngine $engine)
    {
        parent::__construct();
        $this->engine = $engine;
    }

    public function handle(): int
    {
        $this->info('🏦 Starting Daily Settlement Processing...');
        $this->info('═══════════════════════════════════════');

        // Get date to process
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date')) 
            : Carbon::yesterday();

        $this->info("Processing date: {$date->toDateString()}");
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No changes will be saved');
            $this->newLine();
        }

        // Process settlements
        try {
            $results = $this->engine->processDailySettlements($date);

            // Display results
            $this->displayResults($results);

            $created = collect($results)->where('created', true)->count();
            $skipped = collect($results)->where('created', false)->count();

            $this->newLine();
            $this->info('═══════════════════════════════════════');
            $this->info("✅ Processing Complete!");
            $this->info("   Settlements Created: {$created}");
            $this->info("   Merchants Skipped: {$skipped}");
            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error processing settlements: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function displayResults(array $results): void
    {
        foreach ($results as $result) {
            if ($result['created']) {
                $this->line("✓ <info>{$result['merchant_name']}</info>");
                $this->line("  Settlement: {$result['settlement_id']}");
                $this->line("  Transactions: {$result['transaction_count']}");
                $this->line("  Net Amount: INR " . number_format($result['net_amount'], 2));
                $this->newLine();
            } else {
                $this->line("⊘ <comment>{$result['merchant_name']}</comment>");
                $this->line("  {$result['message']}");
                $this->newLine();
            }
        }
    }
}
