<?php

namespace App\Console\Commands;

use App\Models\Merchant;
use Illuminate\Console\Command;

class GenerateMissingMerchantIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'merchants:generate-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate merchant_unique_id for merchants that don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for merchants without unique IDs...');

        $merchantsWithoutId = Merchant::whereNull('merchant_unique_id')->get();

        if ($merchantsWithoutId->isEmpty()) {
            $this->info('✓ All merchants already have unique IDs!');
            return 0;
        }

        $this->info("Found {$merchantsWithoutId->count()} merchant(s) without unique IDs.");
        $this->newLine();

        $bar = $this->output->createProgressBar($merchantsWithoutId->count());
        $bar->start();

        foreach ($merchantsWithoutId as $merchant) {
            $merchant->merchant_unique_id = Merchant::generateMerchantUniqueId();
            $merchant->save();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('✓ Successfully generated unique IDs for all merchants!');
        
        return 0;
    }
}
