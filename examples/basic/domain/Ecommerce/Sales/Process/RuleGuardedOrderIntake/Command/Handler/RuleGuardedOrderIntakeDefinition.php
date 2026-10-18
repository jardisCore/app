<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler;

use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\Action\CheckOrderEligibility;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\Action\PlaceOrder;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\RuleGuardedOrderIntake;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\Route;

/**
 * Definition of the process RuleGuardedOrderIntake: the graph, its routes and the per-node declarations.
 *
 * Hermetic - generated from the process model, rebuilt on every build.
 */
final class RuleGuardedOrderIntakeDefinition
{
    public function __invoke(): ProcessDefinition
    {
        return ProcessDefinition::for(RuleGuardedOrderIntake::class, 'RuleGuardedOrderIntake')
            ->context(RuleGuardedOrderIntakeContext::class)
            ->transactional(true)
            ->validate(RuleGuardedOrderIntakeValidator::class)
            ->node(CheckOrderEligibility::class)
            ->onTrue(PlaceOrder::class)
            ->node(PlaceOrder::class)
            ->input('order')
            ->terminal(Route::OnTrue)
            ->build();
    }
}
