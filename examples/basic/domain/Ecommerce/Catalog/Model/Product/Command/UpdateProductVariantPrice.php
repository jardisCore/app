<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command;

use DateTimeImmutable;

/**
 * Command data class for UpdateProductVariantPrice.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateProductVariantPrice
{
    public function __construct(
        public string $tenant,
        public string $productIdentifier,
        public string $productVariantIdentifier,
        public int $productVariantPriceId,
        public string $region,
        public string $price,
        public string $currency,
        public ?DateTimeImmutable $validFrom,
        public ?DateTimeImmutable $validUntil
    ) {
    }
}
