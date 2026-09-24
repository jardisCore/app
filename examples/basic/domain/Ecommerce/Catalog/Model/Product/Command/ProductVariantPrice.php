<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command;

use DateTimeImmutable;

/**
 * Command data class for ProductVariantPrice.
 *
 * Readonly DTO for command operations.
 */
readonly class ProductVariantPrice
{
    public function __construct(
        public string $region,
        public float $price,
        public string $currency,
        public ?DateTimeImmutable $validFrom,
        public ?DateTimeImmutable $validUntil
    ) {
    }
}
