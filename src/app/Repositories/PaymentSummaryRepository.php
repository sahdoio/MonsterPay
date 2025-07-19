<?php

declare(strict_types=1);

namespace App\Repositories;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;

class PaymentSummaryRepository
{
    #[Inject]
    protected Redis $redis;

    protected const string KEY_TOTAL_REQUESTS = 'summary:%s:requests';
    protected const string KEY_TOTAL_AMOUNT   = 'summary:%s:amount';

    public function register(string $processor, float $amount): void
    {
        $this->redis->incr(sprintf(self::KEY_TOTAL_REQUESTS, $processor));
        $this->redis->incrbyfloat(sprintf(self::KEY_TOTAL_AMOUNT, $processor), $amount);
    }

    public function get(): array
    {
        $types = ['default', 'fallback'];

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
