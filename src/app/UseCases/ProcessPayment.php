<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Clients\ProcessorClient;
use App\Repositories\PaymentSummaryRepository;
use App\Services\HealthMonitor;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class ProcessPayment
{
    #[Inject]
    protected ProcessorClient $client;

    #[Inject]
    protected PaymentSummaryRepository $summary;

    #[Inject]
    protected HealthMonitor $health;

    #[Inject]
    protected ResponseInterface $response;

    public function execute(string $correlationId, float $amount): PsrResponseInterface
    {
        // 1. Try the default processor
        $processor = $this->health->getOptimalProcessor();

        $result = $this->client->send($processor, $correlationId, $amount);

        if ($result['success']) {
            $this->summary->register($processor, $amount);
            return $this->response->json(['status' => 'ok']);
        }

        // 2. Try the fallback processor if the default failed
        if ($processor === 'default') {
            $fallbackResult = $this->client->send('fallback', $correlationId, $amount);
            if ($fallbackResult['success']) {
                $this->summary->register('fallback', $amount);
                return $this->response->json(['status' => 'ok', 'fallback' => true]);
            }
        }

        // 3. If both processors failed, return an error
        return $this->response->json(['error' => 'Failed to process payment'])->withStatus(502);
    }
}
