<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Determine if the user can view the transaction.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->isAdmin() || $user->merchant_id === $transaction->merchant_id;
    }

    /**
     * Determine if the user can create transactions.
     */
    public function create(User $user): bool
    {
        return $user->isMerchant() || $user->isAdmin();
    }
}

