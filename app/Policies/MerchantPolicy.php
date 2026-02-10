<?php

namespace App\Policies;

use App\Models\Merchant;
use App\Models\User;

class MerchantPolicy
{
    /**
     * Determine if the user can view any merchants.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can view the merchant.
     */
    public function view(User $user, Merchant $merchant): bool
    {
        return $user->isAdmin() || $user->merchant_id === $merchant->id;
    }

    /**
     * Determine if the user can create merchants.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can update the merchant.
     */
    public function update(User $user, Merchant $merchant): bool
    {
        return $user->isAdmin() || $user->merchant_id === $merchant->id;
    }

    /**
     * Determine if the user can delete the merchant.
     */
    public function delete(User $user, Merchant $merchant): bool
    {
        return $user->isAdmin();
    }
}

