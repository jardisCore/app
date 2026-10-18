<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Order;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\RuleGuardedOrderIntakeContext;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Handler\Node\MapResponse;
use Throwable;

/**
 * Node PlaceOrder of the process RuleGuardedOrderIntake (call).
 *
 * Ruft den Order-Aggregat-Command Order (createOrder) auf. onFail bei
 * 422 (Rule-Ablehnung der Order-eigenen Kette, Vertrag 5) — ein
 * technischer 5xx-Fehler geht NICHT ueber diese Kante, sondern in den
 * Exception-Pfad der Engine.
 *
 * Calls the facade Ecommerce.Sales.Order, method Order.
 */
final class PlaceOrder extends EcommerceContext
{
    /**
     * @node-id n2
     * @throws Throwable
     */
    public function __invoke(RuleGuardedOrderIntakeContext $ctx): NodeResult
    {
        /** @var DomainResponseInterface $response */
        $response = $this->handle(Order::class)->createOrder($ctx->input()->order);

        return (new MapResponse())($response);
    }
}
