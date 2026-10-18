<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler;

use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\Action\CheckOrderEligibility;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\Action\PlaceOrder;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\RuleGuardedOrderIntake;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Read view of a run of the process RuleGuardedOrderIntake: its input and one typed getter per node.
 *
 * Hermetic - the getters follow the nodes of the process model.
 */
final class RuleGuardedOrderIntakeContext extends ProcessContext
{
    public function input(): RuleGuardedOrderIntake
    {
        /** @var RuleGuardedOrderIntake $input */
        $input = parent::input();

        return $input;
    }

    public function checkOrderEligibility(): ?NodeResult
    {
        return $this->latest(CheckOrderEligibility::class);
    }

    public function placeOrder(): ?NodeResult
    {
        return $this->latest(PlaceOrder::class);
    }
}
