<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use Ecommerce\Catalog\Entity\ProductVariantPrice as EntityProductVariantPrice;

/**
 * Aggregate entity: ProductVariantPrice
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'ProductVariantPrice')]
class ProductVariantPrice extends EntityProductVariantPrice
{
}
