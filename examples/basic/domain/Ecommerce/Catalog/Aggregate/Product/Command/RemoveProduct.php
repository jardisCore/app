<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command;

/**
 * Command data class for RemoveProduct.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveProduct
{
    public function __construct(
        public string $productIdentifier
    ) {
    }
}
