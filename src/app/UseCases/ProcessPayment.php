<?php

namespace App\UseCases;

use App\Clients\ProcessorClient;
use App\DataTransferObjects\OutputPaymentDTO;
use App\Enums\ProcessorType;
use App\Repositories\PaymentSummaryRepository;
use App\Services\HealthMonitor;
use Exception;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;

// use Psr\SimpleCache\CacheInterface;

class ProcessPayment
{
    #[Inject]
    protected ProcessorClient $client;

    #[Inject]
    protected PaymentSummaryRepository $summary;

    #[Inject]
    protected HealthMonitor $health;

    /**
     * TODO: Solve dependency injection for CacheInterface
     * #[Inject]
     * protected CacheInterface $cache;
     */

    #[Inject]
    protected Redis $cache;

    /**
     * @throws Exception
     */
    public function execute(string $correlationId, float $amount): OutputPaymentDTO
    {
        $lockKey = "payment_lock:$correlationId";

        if ($this->cache->exists($lockKey)) {
            throw new Exception('Duplicate correlationId: payment already being processed or processed.', 409);
        }

        $this->cache->set($lockKey, '1');

        $processor = $this->health->getOptimalProcessor();

        $result = $this->client->send($processor, $correlationId, $amount);

        if ($result['success']) {
            $this->summary->register($processor, $amount);
            return new OutputPaymentDTO(status: 'ok', processor: $processor);
        }

        if (ProcessorType::DEFAULT === $processor) {
            $fallbackResult = $this->client->send(ProcessorType::FALLBACK, $correlationId, $amount);

            if ($fallbackResult['success']) {
                $this->summary->register(ProcessorType::FALLBACK, $amount);
                return new OutputPaymentDTO(status: 'ok', processor: ProcessorType::FALLBACK);
            }
        }

        $this->cache->del($lockKey);
        throw new Exception('Payment processing failed for both processors.', 500);
    }
}
