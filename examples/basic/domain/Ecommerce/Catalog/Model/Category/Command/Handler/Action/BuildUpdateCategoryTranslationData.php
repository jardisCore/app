<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Handler\Action;

use Ecommerce\Catalog\FieldMap;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Catalog\Model\Category\Command\UpdateCategoryTranslation as CommandUpdateCategoryTranslation;

/**
 * Action: BuildUpdateCategoryTranslationData
 */
class BuildUpdateCategoryTranslationData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandUpdateCategoryTranslation $updateCategoryTranslation Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandUpdateCategoryTranslation $updateCategoryTranslation): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($updateCategoryTranslation);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["categoryIdentifier","categoryTranslationId"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->categoryTranslationsColumns());
    }
}
