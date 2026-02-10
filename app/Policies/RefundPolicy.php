<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;

class RefundPolicy
{
    /**
     * Determine if the user can view the refund.
     */
    public function view(User $user, Refund $refund): bool
    {
        return $user->isAdmin() || $user->merchant_id === $refund->merchant_id;
    }

    /**
     * Determine if the user can create refunds.
     */
    public function create(User $user): bool
    {
        return $user->isMerchant() || $user->isAdmin();
    }

    /**
     * Determine if the user can update the refund.
     */
    public function update(User $user, Refund $refund): bool
    {
        return $user->isAdmin() || 
               ($user->isMerchant() && $user->merchant_id === $refund->merchant_id && $refund->status === 'pending');
    }
}

