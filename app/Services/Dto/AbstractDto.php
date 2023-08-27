<?php

declare(strict_types=1);

namespace App\Services\Dto;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

abstract class AbstractDto
{
    public function __construct(array $data)
    {
        $validator = Validator::make(
            $data,
            $this->configureValidatorRules()
        );

        if (!$validator->validate()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        $this->map($data);
    }

    abstract protected function configureValidatorRules(): array;

    abstract protected function map(array $data): void;
}
