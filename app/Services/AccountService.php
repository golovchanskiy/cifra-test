<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Repositories\Interfaces\AccountRepositoryInterface;

class AccountService
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function create(int $userId): Account
    {
        $account = new Account();
        $account->setUserId($userId);

        return $this->repository->createOrUpdate($account);
    }

    public function updateBalance(int $userId, float $amount): bool
    {
        $account = $this->repository->find($userId);
        if (!$account) {
            $this->create($userId);
        }

        if ($amount > 0) {
            return $this->repository->incrementBalance($userId, $amount);
        }

        return $this->repository->decrementBalance($userId, abs($amount));
    }
}
