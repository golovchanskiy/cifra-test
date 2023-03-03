<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\TransactionAlreadyExistsException;
use App\Exceptions\TransactionInvalidException;
use App\Exceptions\UserLockException;
use App\Services\AccountService;
use App\Services\Dto\TransactionDto;
use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IncomingTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private AccountService $accountService,
        private TransactionService $transactionService,
    ) {
    }

    public function handle(RabbitMQJob $job, array $data): void
    {
        $logData = [
            'class' => IncomingTransactionJob::class,
            'data' => $data
        ];

        try {
            $dto = new TransactionDto($data);
        } catch (\Exception $e) {
            Log::error('validate error', array_merge($logData, ['error' => $e->getMessage()]));
            $job->delete();
            return;
        }

        try {
            $this->transactionService->processTransaction($dto);
            Log::info('transaction processed', $logData);
            $job->delete();
        } catch (TransactionAlreadyExistsException|TransactionInvalidException $e) {
            Log::error('process error', array_merge($logData, ['error' => $e->getMessage()]));
            $job->delete();
        } catch (UserLockException $e) {
            Log::warning('lock', array_merge($logData, ['error' => $e->getMessage()]));
            $job->release();
        } catch (\Exception $e) {
            Log::error('unknown error', array_merge($logData, ['error' => $e->getMessage()]));
            $job->release();
        }
    }
}
