<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command;

/**
 * Command data class for RemoveProductVariant.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveProductVariant
{
    public function __construct(
        public string $productIdentifier,
        public string $productVariantIdentifier
    ) {
    }
}
