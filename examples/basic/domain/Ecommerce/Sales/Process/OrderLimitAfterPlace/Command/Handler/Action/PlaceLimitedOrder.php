<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Order;
use Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\Handler\OrderLimitAfterPlaceContext;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Handler\Node\MapResponse;
use Throwable;

/**
 * Node PlaceLimitedOrder of the process OrderLimitAfterPlace (call).
 *
 * Command-Knoten: legt die Order an; sein Ergebnis traegt die Identitaet
 * (`orderNumber`) der angelegten Wurzel.
 *
 * Calls the facade Ecommerce.Sales.Order, method Order.
 */
final class PlaceLimitedOrder extends EcommerceContext
{
    /**
     * @node-id n1
     * @throws Throwable
     */
    public function __invoke(OrderLimitAfterPlaceContext $ctx): NodeResult
    {
        /** @var DomainResponseInterface $response */
        $response = $this->handle(Order::class)->createOrder($ctx->input()->order);

        return (new MapResponse())($response);
    }
}
