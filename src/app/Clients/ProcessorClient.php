<?php

declare(strict_types=1);

namespace App\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Di\Annotation\Inject;
use Psr\Log\LoggerInterface;

class ProcessorClient
{
    #[Inject]
    protected Client $http;

    #[Inject]
    protected LoggerInterface $logger;

    protected array $endpoints = [
        'default' => 'http://payment-processor-default:8080/payments',
        'fallback' => 'http://payment-processor-fallback:8080/payments',
    ];

    public function send(string $processor, string $correlationId, float $amount): array
    {
        $url = $this->endpoints[$processor] ?? null;

        if (!$url) {
            $this->logger->error("Unknown processor: $processor");
            return ['success' => false];
        }

        try {
            $response = $this->http->post($url, [
                'json' => [
                    'correlationId' => $correlationId,
                    'amount' => $amount,
                    'requestedAt' => (new \DateTimeImmutable())->format('c'),
                ],
                'timeout' => 0.3,
            ]);

            if ($response->getStatusCode() < 300) {
                return ['success' => true];
            }

            return ['success' => false];

        } catch (GuzzleException $e) {
            $this->logger->warning("Processor [$processor] failed: " . $e->getMessage());
            return ['success' => false];
        }
    }
}
