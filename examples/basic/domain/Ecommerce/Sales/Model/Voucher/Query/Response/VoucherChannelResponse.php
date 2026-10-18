<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query\Response;

/**
 * Read projection for VoucherChannelResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class VoucherChannelResponse
{
    public string $identifier = '';
    public string $label = '';
    public ?ChannelHookResponse $channelHook = null;
}
