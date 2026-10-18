<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\Handler;

use Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\Handler\Action\CheckPlacedOrderWithinLimit;
use Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\Handler\Action\PlaceLimitedOrder;
use Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\OrderLimitAfterPlace;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\Route;

/**
 * Definition of the process OrderLimitAfterPlace: the graph, its routes and the per-node declarations.
 *
 * Hermetic - generated from the process model, rebuilt on every build.
 */
final class OrderLimitAfterPlaceDefinition
{
    public function __invoke(): ProcessDefinition
    {
        return ProcessDefinition::for(OrderLimitAfterPlace::class, 'OrderLimitAfterPlace')
            ->context(OrderLimitAfterPlaceContext::class)
            ->transactional(true)
            ->validate(OrderLimitAfterPlaceValidator::class)
            ->node(PlaceLimitedOrder::class)
            ->input('order')
            ->onTrue(CheckPlacedOrderWithinLimit::class)
            ->node(CheckPlacedOrderWithinLimit::class)
            ->terminal(Route::OnTrue)
            ->build();
    }
}
