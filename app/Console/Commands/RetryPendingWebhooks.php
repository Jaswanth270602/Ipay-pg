<?php

namespace App\Console\Commands;

use App\Jobs\DeliverWebhookJob;
use App\Models\WebhookEvent;
use Illuminate\Console\Command;

class RetryPendingWebhooks extends Command
{
    protected $signature = 'webhooks:retry
                            {--limit=100 : Max webhook events to dispatch per run}';

    protected $description = 'Dispatch delivery jobs for webhook events that are due for retry';

    public function handle(): int
    {
        $limit = max(1, min(500, (int) $this->option('limit')));

        $dispatched = 0;

        WebhookEvent::query()
            ->where('delivered', false)
            ->whereColumn('attempt_count', '<', 'max_attempts')
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            })
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (WebhookEvent $event) use (&$dispatched) {
                if ($event->next_retry_at !== null && $event->next_retry_at->isFuture()) {
                    return;
                }

                if ($event->shouldRetry() || $event->next_retry_at === null) {
                    DeliverWebhookJob::dispatch($event);
                    $dispatched++;
                }
            });

        if ($dispatched > 0) {
            $this->info("Dispatched {$dispatched} webhook delivery job(s).");
        }

        return self::SUCCESS;
    }
}
