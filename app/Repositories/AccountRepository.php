<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Account;
use App\Repositories\Interfaces\AccountRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AccountRepository implements AccountRepositoryInterface
{
    public function __construct(
        private readonly Account $model,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function find(int $userId): ?Account
    {
        /** @var Account $account */
        $account = Account::query()->find([
            'user_id' => $userId,
        ])->first();

        return $account;
    }

    /**
     * @throws \Throwable
     */
    public function createOrUpdate(Account $account): Account
    {
        $account->saveOrFail();

        return $account;
    }

    public function incrementBalance(int $userId, float $amount): bool
    {
        return (bool)DB::table($this->model->getTable())
            ->where('user_id', $userId)
            ->increment('balance', $amount);
    }

    public function decrementBalance(int $userId, float $amount): bool
    {
        return (bool)DB::table($this->model->getTable())
            ->where('user_id', $userId)
            ->decrement('balance', $amount);
    }
}
