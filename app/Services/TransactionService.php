<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TransactionEvent;
use App\Exceptions\TransactionAlreadyExistsException;
use App\Exceptions\TransactionInvalidException;
use App\Exceptions\UserLockException;
use App\Models\Transaction;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Services\Dto\TransactionDto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public const LOCK_NAME = 'user_%d';
    public const LOCK_TTL = 10;

    public function __construct(
        private readonly TransactionRepositoryInterface $repository,
        private readonly AccountService $accountService,
    ) {
    }

    private function checkHoldTransaction(Transaction $transaction, float $amount): bool
    {
        return $amount == $transaction->getAmount()
            && $transaction->getStatus() == Transaction::STATUS_HOLD;
    }

    /**
     * @throws TransactionAlreadyExistsException
     * @throws TransactionInvalidException
     * @throws UserLockException
     */
    public function processTransaction(TransactionDto $dto): void
    {
        $userLock = Cache::lock(sprintf(self::LOCK_NAME, $dto->getUserId()), self::LOCK_TTL);
        if (!$userLock->get()) {
            throw new UserLockException('user locked');
        }

        if ($dto->getSenderUserId()) {
            $senderLock = Cache::lock(sprintf(self::LOCK_NAME, $dto->getSenderUserId()), self::LOCK_TTL);
            if (!$senderLock->get()) {
                throw new UserLockException('sender locked');
            }
        }

        DB::beginTransaction();
        try {
            switch ($dto->getOperation()) {
                case TransactionDto::OPERATION_HOLD:
                    $this->processHold($dto);
                    break;
                case TransactionDto::OPERATION_COMPLETE:
                    $this->processCompete($dto);
                    break;
                case TransactionDto::OPERATION_CANCEL:
                    $this->processCancel($dto);
                    break;
            }

            DB::commit();

            TransactionEvent::dispatch($dto);
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        } finally {
            $userLock->release();
            if (isset($senderLock)) {
                $senderLock->release();
            }
        }
    }

    /**
     * @throws TransactionAlreadyExistsException
     */
    private function processHold(TransactionDto $dto): void
    {
        $this->hold($dto->getId(), $dto->getUserId(), $dto->getAmount());

        if ($dto->getSenderUserId()) {
            $amount = -$dto->getAmount();
            $this->hold($dto->getId(), $dto->getSenderUserId(), $amount);
        }
    }

    /**
     * @throws TransactionAlreadyExistsException
     */
    private function hold(string $transactionId, int $userId, float $amount): void
    {
        $transaction = $this->repository->find($transactionId, $userId);
        if ($transaction) {
            throw new TransactionAlreadyExistsException('transaction already exists');
        }

        $transaction = new Transaction();
        $transaction->setTransactionId($transactionId);
        $transaction->setUserId($userId);
        $transaction->setAmount($amount);
        $transaction->setStatus(Transaction::STATUS_HOLD);

        $transaction = $this->repository->createOrUpdate($transaction);

        // при холдировании меняем баланс счета только при списании
        if ($amount < 0) {
            $this->accountService->updateBalance($userId, $amount);
        }
    }

    /**
     * @throws TransactionInvalidException
     */
    private function processCompete(TransactionDto $dto): void
    {
        $this->complete($dto->getId(), $dto->getUserId(), $dto->getAmount());

        if ($dto->getSenderUserId()) {
            $amount = -$dto->getAmount();

            $this->complete($dto->getId(), $dto->getSenderUserId(), $amount);
        }
    }

    /**
     * @throws TransactionInvalidException
     */
    private function complete(string $transactionId, int $userId, float $amount): void
    {
        $transaction = $this->repository->find($transactionId, $userId);
        $isUnhold = $transaction && $this->checkHoldTransaction($transaction, $amount);

        if ($transaction && !$isUnhold) {
            throw new TransactionInvalidException('invalid transaction');
        }

        if (!$transaction) {
            $transaction = new Transaction();
            $transaction->setTransactionId($transactionId);
            $transaction->setUserId($userId);
            $transaction->setAmount($amount);
        }

        $transaction->setStatus(Transaction::STATUS_COMPLETE);

        $this->repository->createOrUpdate($transaction);

        // меняем баланс счета если не было холдирования или было холдирование поступления
        if (!$isUnhold || $amount > 0) {
            $this->accountService->updateBalance($userId, $amount);
        }
    }

    /**
     * @throws TransactionInvalidException
     */
    private function processCancel(TransactionDto $dto): void
    {
        $this->cancel($dto->getId(), $dto->getUserId(), $dto->getAmount());

        if ($dto->getSenderUserId()) {
            $amount = -$dto->getAmount();

            $this->cancel($dto->getId(), $dto->getSenderUserId(), $amount);
        }
    }

    /**
     * @throws TransactionInvalidException
     */
    private function cancel(string $transactionId, int $userId, float $amount): void
    {
        $transaction = $this->repository->find($transactionId, $userId);
        if (!$transaction || !$this->checkHoldTransaction($transaction, $amount)) {
            throw new TransactionInvalidException('invalid transaction');
        }

        $transaction->setStatus(Transaction::STATUS_CANCELED);

        $this->repository->createOrUpdate($transaction);

        // возвращаем деньги на счёт, если по холдированию было списание
        if ($amount < 0) {
            $this->accountService->updateBalance($userId, abs($amount));
        }
    }
}
