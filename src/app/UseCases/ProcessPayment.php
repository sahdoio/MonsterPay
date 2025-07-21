<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Clients\ProcessorClient;
use App\DataTransferObjects\OutputPaymentDTO;
use App\Enums\ProcessorType;
use App\Repositories\PaymentSummaryRepository;
use App\Services\HealthMonitor;
use Hyperf\Di\Annotation\Inject;
use Exception;

class ProcessPayment
{
    #[Inject]
    protected ProcessorClient $client;

    #[Inject]
    protected PaymentSummaryRepository $summary;

    #[Inject]
    protected HealthMonitor $health;

    /**
     * @throws Exception
     */
    public function execute(string $correlationId, float $amount): OutputPaymentDTO
    {
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

        throw new Exception('Payment processing failed for both processors.', 500);
    }
}
