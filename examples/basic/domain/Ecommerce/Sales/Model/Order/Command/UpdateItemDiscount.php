<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command;

/**
 * Command data class for UpdateItemDiscount.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateItemDiscount
{
    public function __construct(
        public string $tenant,
        public string $orderNumber,
        public string $orderItemIdentifier,
        public int $itemDiscountId,
        public string $discountCode,
        public string $amount
    ) {
    }
}
