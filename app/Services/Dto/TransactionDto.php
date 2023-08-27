<?php

declare(strict_types=1);

namespace App\Services\Dto;

class TransactionDto extends AbstractDto
{

    public const OPERATION_HOLD = 'hold';
    public const OPERATION_COMPLETE = 'complete';
    public const OPERATION_CANCEL = 'cancel';

    private string $id;

    private int $userId;

    private ?int $senderUserId;

    private float $amount;

    private string $operation;

    protected function configureValidatorRules(): array
    {
        return [
            'id' => 'required|uuid',
            'user_id' => 'required|integer',
            'sender_user_id' => 'integer',
            'amount' => 'required|decimal:0,2',
            'operation' => 'required|in:' . implode(',', $this->getOperationList()),
        ];
    }

    protected function map(array $data): void
    {
        $this->id = $data['id'];
        $this->userId = $data['user_id'];
        $this->senderUserId = $data['sender_user_id'] ?? null;
        $this->amount = $data['amount'];
        $this->operation = $data['operation'];
    }

    public function getOperationList(): array
    {
        return [
            self::OPERATION_HOLD,
            self::OPERATION_COMPLETE,
            self::OPERATION_CANCEL,
        ];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getSenderUserId(): ?int
    {
        return $this->senderUserId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }
}
