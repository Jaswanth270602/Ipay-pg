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
                            {--mode= : Filter mode: test or live}
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
            : Carbon::now();

        $merchantId = $this->option('merchant') ? (int) $this->option('merchant') : null;
        $mode = $this->option('mode');
        $dryRun = (bool) $this->option('dry-run');

        if ($mode && !in_array($mode, ['test', 'live'], true)) {
            $this->error("Invalid --mode value: {$mode}. Allowed values are: test, live");
            return self::FAILURE;
        }

        $this->info("Processing date: {$date->toDateString()}");
        if ($merchantId) {
            $this->info("Merchant filter: {$merchantId}");
        }
        if ($mode) {
            $this->info("Mode filter: {$mode}");
        }
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be saved');
            $this->newLine();
        }

        // Process settlements
        try {
            $results = $this->engine->processDailySettlements($date, $merchantId, $mode, $dryRun);

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
