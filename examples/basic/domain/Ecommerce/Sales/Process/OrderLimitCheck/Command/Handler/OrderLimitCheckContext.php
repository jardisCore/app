<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitCheck\Command\Handler;

use Ecommerce\Sales\Process\OrderLimitCheck\Command\Handler\Action\CheckOrderTotalWithinLimit;
use Ecommerce\Sales\Process\OrderLimitCheck\Command\Handler\Action\ResolveOrderNumber;
use Ecommerce\Sales\Process\OrderLimitCheck\Command\OrderLimitCheck;
use Ecommerce\Sales\Process\OrderLimitCheck\Response\ResolveOrderNumberResponse;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Read view of a run of the process OrderLimitCheck: its input and one typed getter per node.
 *
 * Hermetic - the getters follow the nodes of the process model.
 */
final class OrderLimitCheckContext extends ProcessContext
{
    public function input(): OrderLimitCheck
    {
        /** @var OrderLimitCheck $input */
        $input = parent::input();

        return $input;
    }

    public function resolveOrderNumber(): ?ResolveOrderNumberResponse
    {
        $data = $this->latest(ResolveOrderNumber::class)?->data();

        return $data instanceof ResolveOrderNumberResponse ? $data : null;
    }

    public function checkOrderTotalWithinLimit(): ?NodeResult
    {
        return $this->latest(CheckOrderTotalWithinLimit::class);
    }
}
