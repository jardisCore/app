<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command;

use DateTimeImmutable;

/**
 * Command data class for AddProductVariantPrice.
 *
 * Readonly DTO for command operations.
 */
readonly class AddProductVariantPrice
{
    public function __construct(
        public string $productIdentifier,
        public string $productVariantIdentifier,
        public string $region,
        public float $price,
        public string $currency,
        public ?DateTimeImmutable $validFrom,
        public ?DateTimeImmutable $validUntil
    ) {
    }
}
