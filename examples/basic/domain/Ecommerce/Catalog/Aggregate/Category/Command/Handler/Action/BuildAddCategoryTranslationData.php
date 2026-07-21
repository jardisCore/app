<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Command\Handler\Action;

use Ecommerce\Catalog\FieldMap;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Catalog\Aggregate\Category\Command\AddCategoryTranslation as CommandAddCategoryTranslation;

/**
 * Action: BuildAddCategoryTranslationData
 */
class BuildAddCategoryTranslationData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandAddCategoryTranslation $addCategoryTranslation Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandAddCategoryTranslation $addCategoryTranslation): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($addCategoryTranslation);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["categoryIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->categoryTranslationsColumns());
    }
}
