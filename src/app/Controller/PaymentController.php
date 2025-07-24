<?php

declare(strict_types=1);

namespace App\Controller;

use App\UseCases\ProcessPayment;
use App\UseCases\GetPaymentsSummary;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

#[Controller(prefix: "/")]
class PaymentController
{
    #[Inject]
    protected ProcessPayment $processPayment;

    #[Inject]
    protected GetPaymentsSummary $getPaymentsSummary;

    #[RequestMapping(path: "/payments", methods: "POST")]
    public function handle(RequestInterface $request, ResponseInterface $response): PsrResponseInterface
    {
        $data = $request->all();

        if (empty($data['correlationId']) || empty($data['amount'])) {
            return $response->json(['error' => 'Missing correlationId or amount'])->withStatus(400);
        }

        $result = $this->processPayment->execute($data['correlationId'], (float)$data['amount']);

        return $response->json([
            'status' => $result->status,
            'processor' => $result->processor->value,
        ]);
    }

    #[RequestMapping(path: "/payments-summary", methods: "GET")]
    public function summary(ResponseInterface $response): PsrResponseInterface
    {
        $result =  $this->getPaymentsSummary->execute();
        return $response->json($result)->withStatus(200);
    }
}
