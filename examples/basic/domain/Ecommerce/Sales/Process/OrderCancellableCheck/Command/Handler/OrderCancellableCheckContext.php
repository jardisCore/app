<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderCancellableCheck\Command\Handler;

use Ecommerce\Sales\Process\OrderCancellableCheck\Command\Handler\Action\CheckOrderCancellable;
use Ecommerce\Sales\Process\OrderCancellableCheck\Command\OrderCancellableCheck;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Read view of a run of the process OrderCancellableCheck: its input and one typed getter per node.
 *
 * Hermetic - the getters follow the nodes of the process model.
 */
final class OrderCancellableCheckContext extends ProcessContext
{
    public function input(): OrderCancellableCheck
    {
        /** @var OrderCancellableCheck $input */
        $input = parent::input();

        return $input;
    }

    public function checkOrderCancellable(): ?NodeResult
    {
        return $this->latest(CheckOrderCancellable::class);
    }
}
