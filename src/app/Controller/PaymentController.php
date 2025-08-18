<?php

declare(strict_types=1);

namespace App\Controller;

use App\Jobs\ProcessPaymentJob;
use App\UseCases\GetPaymentsSummary;
use App\UseCases\ProcessPayment;
use Exception;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Redis\Redis;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

#[Controller(prefix: "/")]
class PaymentController
{
    #[Inject]
    protected GetPaymentsSummary $getPaymentsSummary;

    #[Inject]
    /**
     * TODO: Migrate to a cache service class.
     *       That abstracts the Redis logic.
     *       This will allow for easier testing and future changes.
     *       The current implementation is tightly coupled to the Redis client.
     */
    protected Redis $redis;

    /**
     * @var DriverInterface
     *
     * TODO: migrate to a queue service class.
     *       That abstracts the driver logic.
     *       This will allow for easier testing and future changes.
     *       The current implementation is tightly coupled to the async queue driver.
     */
    protected $driver;

    public function __construct(DriverFactory $driverFactory)
    {
        $this->driver = $driverFactory->get('default');
    }

    /**
     * @throws Exception
     */
    #[RequestMapping(path: "/payments", methods: "POST")]
    public function handle(RequestInterface $request, ResponseInterface $response): PsrResponseInterface
    {
        $data = $request->all();

        if (empty($data['correlationId']) || !isset($data['amount'])) {
            return $response->json(['error' => 'Missing correlationId or amount'])->withStatus(400);
        }

        $correlationId = (string)$data['correlationId'];
        $amount = (float)$data['amount'];

        $this->driver->push(new ProcessPaymentJob($correlationId, $amount));

        return $response->json([
            'message' => 'Payment processing started',
            'correlationId' => $correlationId,
            'amount' => $amount,
        ])->withStatus(202);
    }

    #[RequestMapping(path: "/payments-summary", methods: "GET")]
    public function summary(ResponseInterface $response): PsrResponseInterface
    {
        $result = $this->getPaymentsSummary->execute();
        return $response->json($result);
    }
}
