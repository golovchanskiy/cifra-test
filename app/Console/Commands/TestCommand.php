<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\TransactionAlreadyExistsException;
use App\Exceptions\TransactionInvalidException;
use App\Exceptions\UserLockException;
use App\Jobs\RabbitMQJob;
use App\Services\Dto\TransactionDto;
use App\Services\TransactionService;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TestCommand extends Command
{
    use Dispatchable;

    protected $signature = 'app:test-command';

    protected $description = 'Create test messages';

    public function __construct(
        private readonly TransactionService $transactionService,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $holdTransaction = Str::orderedUuid()->toString();
        $holdUser = 1;
        $holdSender = 2;

        $completeTransaction = Str::orderedUuid()->toString();
        $completeUser = 3;
        $completeSender = 4;

        $cancelTransaction = Str::orderedUuid()->toString();
        $cancelUser = 5;
        $cancelSender = 6;

        $singleHoldTransaction = Str::orderedUuid()->toString();
        $singleHoldUser = 7;

        $dataList = [
            // перевод: холд и списание
            [
                'id' => $holdTransaction,
                'user_id' => $holdUser,
                'sender_user_id' => $holdSender,
                'amount' => 1000,
                'operation' => TransactionDto::OPERATION_HOLD,
            ],

            // первод: списание
            [
                'id' => $completeTransaction,
                'user_id' => $completeUser,
                'sender_user_id' => $completeSender,
                'amount' => 1000,
                'operation' => TransactionDto::OPERATION_COMPLETE,
            ],
            // перевод: холд и отмена
            [
                'id' => $cancelTransaction,
                'user_id' => $cancelUser,
                'sender_user_id' => $cancelSender,
                'amount' => 1000,
                'operation' => TransactionDto::OPERATION_HOLD,
            ],
            [
                'id' => $cancelTransaction,
                'user_id' => $cancelUser,
                'sender_user_id' => $cancelSender,
                'amount' => 1000,
                'operation' => TransactionDto::OPERATION_CANCEL,
            ],
            // перевод: холд и списание
            [
                'id' => $singleHoldTransaction,
                'user_id' => $singleHoldUser,
                'amount' => 7777,
                'operation' => TransactionDto::OPERATION_HOLD,
            ],
            [
                'id' => $singleHoldTransaction,
                'user_id' => $singleHoldUser,
                'amount' => 7777,
                'operation' => TransactionDto::OPERATION_COMPLETE,
            ],
            // ошибка валдации: amount должно быть int или float до 2 занков после запятой
            [
                'id' => $cancelTransaction,
                'user_id' => $cancelUser,
                'sender_user_id' => $cancelSender,
                'amount' => 'error',
                'operation' => TransactionDto::OPERATION_CANCEL,
            ],
            // ошибка валдации: некорректная операция
            [
                'id' => $cancelTransaction,
                'user_id' => $cancelUser,
                'sender_user_id' => $cancelSender,
                'amount' => 'error',
                'operation' => 'error',
            ],
            // ошибка валидации: нет user_id
            [
                'id' => $cancelTransaction,
                'sender_user_id' => $cancelSender,
                'amount' => 1000,
                'operation' => TransactionDto::OPERATION_CANCEL,
            ],
        ];

        foreach ($dataList as $data) {
            $this->sync($data);
        }
    }

    private function async(array $data): void
    {
        Queue::connection('rabbitmq')
            ->pushRaw(json_encode($data), RabbitMQJob::QUEUE_INCOMING_TRANSACTIONS);

        Log::info('sent test message', ['class' => self::class, 'data' => $data]);
    }

    private function sync(array $data): void
    {
        try {
            $this->transactionService->processTransaction(new TransactionDto($data));

            Log::info('transaction processed', $data);
        } catch (ValidationException $e) {
            Log::warning('validation', array_merge($data, ['error' => $e->getMessage()]));
        } catch (UserLockException $e) {
            Log::warning('lock', array_merge($data, ['error' => $e->getMessage()]));
        } catch (TransactionAlreadyExistsException|TransactionInvalidException $e) {
            Log::error('process error', array_merge($data, ['error' => $e->getMessage()]));
        }
    }
}
