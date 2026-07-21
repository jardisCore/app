<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command;

/**
 * Command data class for RemoveProductImage.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveProductImage
{
    public function __construct(
        public string $productIdentifier,
        public int $productImageId
    ) {
    }
}
