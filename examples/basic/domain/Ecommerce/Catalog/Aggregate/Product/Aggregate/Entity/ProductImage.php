<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use Ecommerce\Catalog\Entity\ProductImage as EntityProductImage;

/**
 * Aggregate entity: ProductImage
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'ProductImage')]
class ProductImage extends EntityProductImage
{
}
