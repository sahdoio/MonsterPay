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
use Psr\Log\LoggerInterface;

// TODO: Solve dependency injection for CacheInterface
// use Psr\SimpleCache\CacheInterface;

class ProcessPayment
{
    #[Inject]
    protected ProcessorClient $client;

    #[Inject]
    protected PaymentSummaryRepository $summary;

    #[Inject]
    protected HealthMonitor $health;

    #[Inject]
    protected LoggerInterface $logger;

    /**
     * TODO: Solve dependency injection for CacheInterface
     * #[Inject]
     * protected CacheInterface $cache;
     */

    #[Inject]
    /**
     * TODO: Migrate to a cache service class.
     *       That abstracts the Redis logic.
     *       This will allow for easier testing and future changes.
     *       The current implementation is tightly coupled to the Redis client.
     */
    protected Redis $cache;

    protected string $lockKey;

    /**
     * @throws Exception
     */
    public function execute(string $correlationId, float $amount): OutputPaymentDTO
    {
        $this->logger->info('[ProcessPayment] Processing payment', [
            'correlationId' => $correlationId,
            'amount' => $amount,
        ]);

        if (!$this->verifyCache($correlationId)) {
            throw new Exception('Duplicated correlationId: payment already being processed or processed.', 409);
        }

        $processor = $this->health->getOptimalProcessor();

        $result = $this->client->send($processor, $correlationId, $amount);

        var_dump('[ProcessPayment] Result from processor: ', $result);

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

        if ($this->lockKey) {
            $this->cache->del($this->lockKey);
        }

        throw new Exception('Payment processing failed for both processors.', 500);
    }

    private function verifyCache(string $correlationId): bool
    {
        $this->lockKey = "payment_lock:$correlationId";

        if ($this->cache->exists($this->lockKey)) {
            return false;
        }

        $this->cache->set($this->lockKey, '1');

        return true;
    }
}
