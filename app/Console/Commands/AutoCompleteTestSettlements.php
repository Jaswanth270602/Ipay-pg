<?php

namespace App\Console\Commands;

use App\Services\Settlements\SettlementCompletionService;
use Illuminate\Console\Command;

class AutoCompleteTestSettlements extends Command
{
    protected $signature = 'settlements:auto-complete-test';

    protected $description = 'Auto-mark pending test settlements as settled after 5 minutes (sandbox; no bank API)';

    public function handle(SettlementCompletionService $completion): int
    {
        $n = $completion->autoCompleteEligibleTestSettlements();
        if ($n > 0) {
            $this->info("Auto-completed {$n} test settlement(s).");
        }

        return self::SUCCESS;
    }
}
