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
        private TransactionService $transactionService,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $hold_transaction = Str::orderedUuid()->toString();
        $hold_user = 1;
        $hold_sender = 2;

        $complete_transaction = Str::orderedUuid()->toString();
        $complete_user = 3;
        $complete_sender = 4;

        $cancel_transaction = Str::orderedUuid()->toString();
        $cancel_user = 5;
        $cancel_sender = 6;

        $single_hold_transaction = Str::orderedUuid()->toString();
        $single_hold_user = 7;

        $dataList = [
            // перевод: холд и списание
            [
                'id' => $hold_transaction,
                'user_id' => $hold_user,
                'sender_user_id' => $hold_sender,
                'amount' => 1000,
                'operation' => TransactionDto::OPERATION_HOLD,
            ],

            // первод: списание
            [
                'id' => $complete_transaction,
                'user_id' => $complete_user,
                'sender_user_id' => $complete_sender,
                'amount' => 1000,
                'operation' => TransactionDto::OPERATION_COMPLETE,
            ],
            // перевод: холд и отмена
            [
                'id' => $cancel_transaction,
                'user_id' => $cancel_user,
                'sender_user_id' => $cancel_sender,
                'amount' => 1000,
                'operation' => TransactionDto::OPERATION_HOLD,
            ],
            [
                'id' => $cancel_transaction,
                'user_id' => $cancel_user,
                'sender_user_id' => $cancel_sender,
                'amount' => 1000,
                'operation' => TransactionDto::OPERATION_CANCEL,
            ],
            // перевод: холд и списание
            [
                'id' => $single_hold_transaction,
                'user_id' => $single_hold_user,
                'amount' => 7777,
                'operation' => TransactionDto::OPERATION_HOLD,
            ],
            [
                'id' => $single_hold_transaction,
                'user_id' => $single_hold_user,
                'amount' => 7777,
                'operation' => TransactionDto::OPERATION_COMPLETE,
            ],
            // ошибка валдации: amount должно быть int или float до 2 занков после запятой
            [
                'id' => $cancel_transaction,
                'user_id' => $cancel_user,
                'sender_user_id' => $cancel_sender,
                'amount' => 'error',
                'operation' => TransactionDto::OPERATION_CANCEL,
            ],
            // ошибка валдации: некорректная операция
            [
                'id' => $cancel_transaction,
                'user_id' => $cancel_user,
                'sender_user_id' => $cancel_sender,
                'amount' => 'error',
                'operation' => 'error',
            ],
            // ошибка валдации: нет user_id
            [
                'id' => $cancel_transaction,
                'sender_user_id' => $cancel_sender,
                'amount' => 1000,
                'operation' => TransactionDto::OPERATION_CANCEL,
            ],
        ];

        foreach ($dataList as $data) {
            $this->async($data);
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
            $dto = new TransactionDto($data);
            $this->transactionService->processTransaction($dto);
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
