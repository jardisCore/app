<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use Ecommerce\Catalog\Entity\CategoryTranslation as EntityCategoryTranslation;

/**
 * Aggregate entity: CategoryTranslation
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'CategoryTranslation')]
class CategoryTranslation extends EntityCategoryTranslation
{
}
