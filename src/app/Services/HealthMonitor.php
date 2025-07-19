<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use Hyperf\Di\Annotation\Inject;
use Psr\SimpleCache\CacheInterface;
use Psr\Log\LoggerInterface;

class HealthMonitor
{
    #[Inject]
    protected Client $http;

    #[Inject]
    protected CacheInterface $cache;

    #[Inject]
    protected LoggerInterface $logger;

    protected array $endpoints = [
        'default' => 'http://payment-processor-default:8080/payments/service-health',
        'fallback' => 'http://payment-processor-fallback:8080/payments/service-health',
    ];

    public function getOptimalProcessor(): string
    {
        $defaultHealthy = $this->isHealthy('default');
        $fallbackHealthy = $this->isHealthy('fallback');

        if ($defaultHealthy) {
            return 'default';
        }

        if ($fallbackHealthy) {
            return 'fallback';
        }

        return 'default';
    }

    protected function isHealthy(string $processor): bool
    {
        $cacheKey = "processor_health:{$processor}";

        // Uses cache to avoid repeated checks
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $url = $this->endpoints[$processor] ?? null;

        try {
            $res = $this->http->get($url, ['timeout' => 0.2]);
            $data = json_decode((string)$res->getBody(), true);

            $healthy = !$data['failing'];
            $this->cache->set($cacheKey, $healthy, 5);
            return $healthy;

        } catch (\Throwable $e) {
            $this->logger->warning("Health check failed for [$processor]: " . $e->getMessage());
            $this->cache->set($cacheKey, false, 5);
            return false;
        }
    }
}
