<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Repositories\PaymentSummaryRepository;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class GetPaymentsSummary
{
    #[Inject]
    protected PaymentSummaryRepository $summary;

    #[Inject]
    protected ResponseInterface $response;

    public function execute(): PsrResponseInterface
    {
        $data = $this->summary->get();

        return $this->response->json($data);
    }
}
