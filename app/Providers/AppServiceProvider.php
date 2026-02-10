<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Services\BankProviders\BankProviderInterface;
use App\Services\BankProviders\SandboxBankProvider;
use App\Services\BankProviders\ProductionBankProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind bank provider by merchant mode; fallback to app mode
        $this->app->bind(BankProviderInterface::class, function ($app) {
            $user = auth()->user();
            $merchant = $user ? $user->merchant : null;
            if ($merchant) {
                return $merchant->test_mode ? new SandboxBankProvider() : new ProductionBankProvider();
            }
            $mode = config('badlicash.mode', 'test');
            return $mode === 'test' ? new SandboxBankProvider() : new ProductionBankProvider();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}

