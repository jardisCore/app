<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query\Response;

/**
 * Read projection for ChannelHookResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class ChannelHookResponse
{
    public int $id = 0;
    public string $targetUrl = '';
}
