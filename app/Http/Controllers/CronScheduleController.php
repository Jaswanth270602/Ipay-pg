<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CronScheduleController extends Controller
{
    /**
     * Ping this URL from a server cron or a service like cron-job.org (GET, daily or every minute).
     * Requires SCHEDULER_CRON_TOKEN in .env and ?token= matching that value.
     */
    public function run(Request $request): Response
    {
        $configured = config('ipay.scheduler_cron_token');
        if ($configured === null || $configured === '') {
            abort(503, 'Scheduler cron URL is not configured.');
        }

        $given = (string) $request->query('token', '');
        if (! hash_equals((string) $configured, $given)) {
            abort(403, 'Invalid token.');
        }

        try {
            Artisan::call('schedule:run');

            return response('OK', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        } catch (\Throwable $e) {
            Log::error('Cron schedule:run failed', ['error' => $e->getMessage()]);

            return response('Scheduler error', 500)->header('Content-Type', 'text/plain; charset=UTF-8');
        }
    }
}
