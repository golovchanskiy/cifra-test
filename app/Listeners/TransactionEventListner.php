<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TransactionEvent;
use Illuminate\Support\Facades\Log;

class TransactionEventListner
{
    public function handle(TransactionEvent $event): void
    {
        Log::info('listen TransactionEvent', [
            'class' => TransactionEventListner::class,
            'transaction_id' => $event->transactionDto->getId(),
        ]);
    }
}
