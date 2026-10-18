<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Response;

/**
 * Response contract of the UpdateItemDiscount command.
 *
 * Carries the reference values the command handler writes into
 * DomainResponse::setData() — the aggregate's public identifier plus the
 * affected child reference. Never a state copy: the caller reads the new
 * state back through the read facade.
 */
class UpdateItemDiscountResponse
{
    public string $orderNumber = '';
    public string $orderItemIdentifier = '';
    public int $itemDiscountId = 0;
}
