<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderCancellableCheck\Command\Handler;

use Ecommerce\Sales\Process\OrderCancellableCheck\Command\Handler\Action\CheckOrderCancellable;
use Ecommerce\Sales\Process\OrderCancellableCheck\Command\OrderCancellableCheck;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\Route;

/**
 * Definition of the process OrderCancellableCheck: the graph, its routes and the per-node declarations.
 *
 * Hermetic - generated from the process model, rebuilt on every build.
 */
final class OrderCancellableCheckDefinition
{
    public function __invoke(): ProcessDefinition
    {
        return ProcessDefinition::for(OrderCancellableCheck::class, 'OrderCancellableCheck')
            ->context(OrderCancellableCheckContext::class)
            ->transactional(true)
            ->validate(OrderCancellableCheckValidator::class)
            ->node(CheckOrderCancellable::class)
            ->terminal(Route::OnTrue)
            ->build();
    }
}
