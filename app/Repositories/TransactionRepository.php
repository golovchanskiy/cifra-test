<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Transaction;
use App\Repositories\Interfaces\TransactionRepositoryInterface;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function find(string $transactionId, int $userId): ?Transaction
    {
        /** @var ?Transaction $transaction */
        $transaction = Transaction::query()
            ->where('transaction_id', '=', $transactionId)
            ->where('user_id', '=', $userId)
            ->get()
            ->first();

        return $transaction;
    }

    public function createOrUpdate(Transaction $transaction): Transaction
    {
        $transaction->save();

        return $transaction;
    }
}
