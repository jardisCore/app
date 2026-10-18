<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitCheck\Response;

/**
 * Typed answer of the node ResolveOrderNumber of the process OrderLimitCheck.
 *
 * Hermetic - the fields are the declared response of the node.
 */
final readonly class ResolveOrderNumberResponse
{
    public function __construct(
        public string $orderNumber,
    ) {
    }
}
