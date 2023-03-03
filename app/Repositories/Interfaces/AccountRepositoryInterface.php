<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Account;

interface AccountRepositoryInterface
{
    public function find(int $userId): ?Account;

    public function createOrUpdate(Account $account): Account;

    public function incrementBalance(int $userId, float $amount): bool;

    public function decrementBalance(int $userId, float $amount): bool;
}
