<?php

declare(strict_types=1);

namespace App\Clients;

use App\Enums\ProcessorType;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Di\Annotation\Inject;
use Psr\Log\LoggerInterface;
use function Hyperf\Support\env;

class ProcessorClient
{
    #[Inject]
    protected Client $http;

    #[Inject]
    protected LoggerInterface $logger;

    protected array $endpoints;

    public function __init(): void
    {
        $this->endpoints = [
            ProcessorType::DEFAULT->value => env('PROCESSOR_DEFAULT_URL') . '/payments',
            ProcessorType::FALLBACK->value => env('PROCESSOR_FALLBACK_URL') . '/payments',
        ];

        $this->logger->info(sprintf('endpoints initialized: %s', json_encode($this->endpoints)));
    }

    public function send(ProcessorType $processor, string $correlationId, float $amount): array
    {
        $url = $this->endpoints[$processor->value] ?? null;

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
