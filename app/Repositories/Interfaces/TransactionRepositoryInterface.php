<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Transaction;

interface TransactionRepositoryInterface
{
    public function find(string $transactionId, int $userId): ?Transaction;

    public function createOrUpdate(Transaction $transaction): Transaction;
}
