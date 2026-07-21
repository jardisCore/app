<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Command\Handler\Action;

use Ecommerce\Catalog\FieldMap;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Catalog\Aggregate\Category\Command\UpdateCategory as CommandUpdateCategory;

/**
 * Action: BuildUpdateCategoryData
 */
class BuildUpdateCategoryData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandUpdateCategory $updateCategory Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandUpdateCategory $updateCategory): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($updateCategory);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["categoryIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->categoriesColumns());
    }
}
