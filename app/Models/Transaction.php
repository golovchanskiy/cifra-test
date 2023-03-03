<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public const STATUS_HOLD = 1;
    public const STATUS_COMPLETE = 2;
    public const STATUS_CANCELED = 3;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'amount',
        'status',
    ];

    public function getTransactionId(): string
    {
        return $this->transaction_id;
    }

    public function setTransactionId(string $transactionId): void
    {
        $this->transaction_id = $transactionId;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $userId): void
    {
        $this->user_id = $userId;
    }

    public function getAmount(): float
    {
        return (float)$this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }
}
