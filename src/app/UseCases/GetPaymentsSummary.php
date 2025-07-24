<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Repositories\PaymentSummaryRepository;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\ResponseInterface;

class GetPaymentsSummary
{
    #[Inject]
    protected PaymentSummaryRepository $summary;

    #[Inject]
    protected ResponseInterface $response;

    public function execute(): array
    {
        return $this->summary->get();
    }
}
