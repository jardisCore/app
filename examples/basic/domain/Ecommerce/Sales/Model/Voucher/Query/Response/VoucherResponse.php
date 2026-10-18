<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query\Response;

use JardisSupport\Contract\Workflow\AggregateResponse;

/**
 * Read projection for VoucherResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class VoucherResponse implements AggregateResponse
{
    public string $voucherCode = '';

    /** @var numeric-string */
    public string $amount = '0';
    public ?string $note = null;
    public string $issuedBy = '';
    public string $issuerId = '';
    public ?string $createdAt = null;
    public ?VoucherChannelResponse $voucherChannel = null;
}
