<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitCheck\Command\Handler;

use Ecommerce\Sales\Process\OrderLimitCheck\Command\Handler\Action\CheckOrderTotalWithinLimit;
use Ecommerce\Sales\Process\OrderLimitCheck\Command\Handler\Action\ResolveOrderNumber;
use Ecommerce\Sales\Process\OrderLimitCheck\Command\OrderLimitCheck;
use Ecommerce\Sales\Process\OrderLimitCheck\Response\ResolveOrderNumberResponse;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\Route;

/**
 * Definition of the process OrderLimitCheck: the graph, its routes and the per-node declarations.
 *
 * Hermetic - generated from the process model, rebuilt on every build.
 */
final class OrderLimitCheckDefinition
{
    public function __invoke(): ProcessDefinition
    {
        return ProcessDefinition::for(OrderLimitCheck::class, 'OrderLimitCheck')
            ->context(OrderLimitCheckContext::class)
            ->transactional(true)
            ->validate(OrderLimitCheckValidator::class)
            ->node(ResolveOrderNumber::class)
            ->onTrue(CheckOrderTotalWithinLimit::class)
            ->response(ResolveOrderNumberResponse::class, 'orderNumber')
            ->node(CheckOrderTotalWithinLimit::class)
            ->terminal(Route::OnTrue)
            ->build();
    }
}
