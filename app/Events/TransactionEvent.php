<?php

declare(strict_types=1);

namespace App\Events;

use App\Services\Dto\TransactionDto;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TransactionDto $transactionDto
    ) {
    }
}
