<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Order;
use Throwable;
use Ecommerce\Sales\Model\Order\Aggregate\Order as OrderHandler;
use Ecommerce\Sales\Model\Order\Query\OrderByOrderNumber as QueryOrder;

/**
 * Rule-Knoten-Wurzel-Lader fuer Order (rules-neuverortung PRD I1/§8.3):
 * laedt ueber denselben Repository-Weg wie der generierte
 * Command-Handler (Query + Repository-Getter) — nie ueber die
 * Lesefassade. Generiert (ForceOverwrite), eine Instanz je Aggregat.
 */
final class LoadOrderForRule extends EcommerceContext
{
    /**
     * @throws Throwable
     */
    public function __invoke(string $tenant, string $orderNumber): ?Order
    {
        $query = $this->handle(QueryOrder::class, tenant: $tenant, orderNumber: $orderNumber);
        /** @var OrderHandler|null $handler */
        $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);

        return $handler?->getData();
    }
}
