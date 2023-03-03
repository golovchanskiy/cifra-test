<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob as BaseJob;

class RabbitMQJob extends BaseJob
{
    public const QUEUE_INCOMING_TRANSACTIONS = 'incoming_transactions';

    public function payload()
    {
        // сообщения от Laravel не трогаем
        if ($this->getJobId()) {
            return parent::payload();
        }

        $data = json_decode($this->getRawBody(), true);

        // определяем Job по очереди
        $class = match ($this->queue) {
            self::QUEUE_INCOMING_TRANSACTIONS => IncomingTransactionJob::class,
            default => null,
        };

        if (!$class) {
            Log::error('unknown message', [
                'queue' => $this->queue,
                'rawBody' => $data,
            ]);
        }

        return [
            'job' => $class . '@handle',
            'data' => $data,
        ];
    }
}
