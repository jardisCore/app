<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Process\UpdateProductInCatalog\Command;

use Ecommerce\Catalog\Aggregate\Product\Command\UpdateProduct;

/**
 * Process DTO for UpdateProductInCatalog.
 *
 * Readonly DTO for command/query operations.
 */
readonly class UpdateProductInCatalog
{
    public function __construct(
        public UpdateProduct $update
    ) {
    }
}
