<?php

declare(strict_types=1);

namespace App\Jobs;

use App\UseCases\ProcessPayment;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Exception;

final class ProcessPaymentJob extends Job
{
    public function __construct(
        public string $correlationId,
        public float  $amount
    )
    {
        $this->maxAttempts = 5;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function handle(): void
    {
        $c = ApplicationContext::getContainer();

        /** @var ProcessPayment $useCase */
        $useCase = $c->get(ProcessPayment::class);
        /** @var LoggerInterface $logger */
        $logger = $c->get(LoggerInterface::class);

        try {
            $useCase->execute($this->correlationId, $this->amount);
        } catch (Exception $e) {
            $logger->error('ProcessPaymentJob failed', [
                'correlationId' => $this->correlationId,
                'amount' => $this->amount,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
