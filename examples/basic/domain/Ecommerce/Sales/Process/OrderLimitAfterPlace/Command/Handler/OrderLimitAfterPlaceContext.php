<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\Handler;

use Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\Handler\Action\CheckPlacedOrderWithinLimit;
use Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\Handler\Action\PlaceLimitedOrder;
use Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\OrderLimitAfterPlace;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Read view of a run of the process OrderLimitAfterPlace: its input and one typed getter per node.
 *
 * Hermetic - the getters follow the nodes of the process model.
 */
final class OrderLimitAfterPlaceContext extends ProcessContext
{
    public function input(): OrderLimitAfterPlace
    {
        /** @var OrderLimitAfterPlace $input */
        $input = parent::input();

        return $input;
    }

    public function placeLimitedOrder(): ?NodeResult
    {
        return $this->latest(PlaceLimitedOrder::class);
    }

    public function checkPlacedOrderWithinLimit(): ?NodeResult
    {
        return $this->latest(CheckPlacedOrderWithinLimit::class);
    }
}
