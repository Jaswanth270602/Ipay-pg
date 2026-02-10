<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Refund;
use App\Models\Merchant;
use App\Policies\OrderPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\RefundPolicy;
use App\Policies\MerchantPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Order::class => OrderPolicy::class,
        Transaction::class => TransactionPolicy::class,
        Refund::class => RefundPolicy::class,
        Merchant::class => MerchantPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define gates for admin access
        Gate::define('admin-access', function (User $user) {
            return $user->role && $user->role->name === 'admin';
        });

        // Define gates for merchant access
        Gate::define('merchant-access', function (User $user) {
            return $user->role && $user->role->name === 'merchant';
        });

        // Define gate for viewing all merchants (admin only)
        Gate::define('view-all-merchants', function (User $user) {
            return $user->role && $user->role->name === 'admin';
        });

        // Define gate for managing settlements
        Gate::define('manage-settlements', function (User $user) {
            return $user->role && in_array($user->role->name, ['admin', 'merchant']);
        });
    }
}

