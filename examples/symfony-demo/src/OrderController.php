<?php

declare(strict_types=1);

namespace SymfonyDemo;

use Ecommerce\Ecommerce;
use Ecommerce\Sales\Aggregate\Order\Query\OrderById;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * N3 illustration (PRD N3, PLAN P8 Block A): a thin Symfony controller
 * calling the SAME Sales BC outer door (F6, `order(): OrderRead` read
 * facade) that `examples/basic/app/RegisterOrderRoutes.php` calls for
 * `GET /orders/{id}` -- proving that the Sales facade neither knows nor
 * cares which framework sits on top of it (the Wandfreiheit struct-proof,
 * PLAN P7, holds for Symfony exactly as it holds for the Jardis router).
 */
final class OrderController
{
    public function __construct(private readonly DomainKernelInterface $kernel)
    {
    }

    public function getOrderById(string $id): Response
    {
        $sales = (new Ecommerce($this->kernel))->sales();
        $domainResponse = $sales->order()->getOrderById(new OrderById((int) $id));

        return (new MapDomainResponseToJsonResponse())($domainResponse);
    }
}
