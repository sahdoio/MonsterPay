<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ProcessorType;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;

class PaymentSummaryRepository
{
    #[Inject]
    protected Redis $redis;

    protected const string KEY_TOTAL_REQUESTS = 'summary:%s:requests';
    protected const string KEY_TOTAL_AMOUNT   = 'summary:%s:amount';

    public function register(ProcessorType $processor, float $amount): void
    {
        $this->redis->incr(sprintf(self::KEY_TOTAL_REQUESTS, $processor->value));
        $this->redis->incrbyfloat(sprintf(self::KEY_TOTAL_AMOUNT, $processor->value), $amount);
    }

    public function get(): array
    {
        $types = [ProcessorType::DEFAULT->value, ProcessorType::FALLBACK->value];

        $summary = [];

        foreach ($types as $type) {
            $summary[$type] = [
                'totalRequests' => (int) $this->redis->get(sprintf(self::KEY_TOTAL_REQUESTS, $type)) ?: 0,
                'totalAmount'   => (float) $this->redis->get(sprintf(self::KEY_TOTAL_AMOUNT, $type)) ?: 0.0,
            ];
        }

        return $summary;
    }
}
