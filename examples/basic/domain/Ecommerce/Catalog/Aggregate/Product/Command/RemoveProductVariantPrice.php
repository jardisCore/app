<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command;

/**
 * Command data class for RemoveProductVariantPrice.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveProductVariantPrice
{
    public function __construct(
        public string $productIdentifier,
        public string $productVariantIdentifier,
        public int $productVariantPriceId
    ) {
    }
}
