<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProcessorType;
use GuzzleHttp\Client;
use Hyperf\Di\Annotation\Inject;
use Psr\SimpleCache\CacheInterface;
use Psr\Log\LoggerInterface;
use function Hyperf\Support\env;
use Throwable;

class HealthMonitor
{
    #[Inject]
    protected Client $http;

    #[Inject]
    protected CacheInterface $cache;

    #[Inject]
    protected LoggerInterface $logger;

    protected array $endpoints;

    public function __construct()
    {
        $this->endpoints = [
            ProcessorType::DEFAULT->value => env('PROCESSOR_DEFAULT_URL') . '/payments/service-health',
            ProcessorType::FALLBACK->value => env('PROCESSOR_FALLBACK_URL') . '/payments/service-health',
        ];
    }

    public function getOptimalProcessor(): ProcessorType
    {
        $defaultHealthy = $this->isHealthy(ProcessorType::DEFAULT);
        $fallbackHealthy = $this->isHealthy(ProcessorType::FALLBACK);

        if ($defaultHealthy) {
            return ProcessorType::DEFAULT;
        }

        if ($fallbackHealthy) {
            return ProcessorType::FALLBACK;
        }

        return ProcessorType::DEFAULT;
    }

    protected function isHealthy(ProcessorType $processor): bool
    {
        $key = $processor->value;
        $cacheKey = "processor_health:$key";

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $url = $this->endpoints[$key] ?? null;

        try {
            $res = $this->http->get($url, ['timeout' => 0.2]);
            $data = json_decode((string)$res->getBody(), true);
            $healthy = !$data['failing'];
            $this->cache->set($cacheKey, $healthy, 5);
            return $healthy;
        } catch (Throwable $e) {
            $this->logger->error("error: ", [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            $this->logger->warning("Health check failed for [$key]: " . $e->getMessage());
            $this->cache->set($cacheKey, false, 5);
            return false;
        }
    }
}
