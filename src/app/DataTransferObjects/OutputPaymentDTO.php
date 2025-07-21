<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\ProcessorType;

class OutputPaymentDTO
{
    public function __construct(
        public string $status,
        public ProcessorType $processor
    ) {}
}
