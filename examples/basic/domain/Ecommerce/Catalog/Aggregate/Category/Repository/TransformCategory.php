<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Repository;

use Ecommerce\EcommerceContext;

/**
 * Transforms flat query results into nested Category structure.
 *
 * Input: Arrays grouped by entity name (from query results)
 * Output: Nested array structure matching aggregate definition
 */
class TransformCategory extends EcommerceContext
{
    /**
     * Transform flat query results into nested aggregate structure.
     *
     * @param array<string, array<int, mixed>> $container Query results grouped by entity
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(array $container = []): array
    {
        $result = [];
        $dto = [];
        $processedIds = [];

        foreach ($container['category'] as $index => $category) {
            if (is_numeric($index) && !isset($processedIds[$category['id']])) {
                $processedIds[$category['id']] = true;
                $parent = $dto['category'] = $category;

                $this->transformCategoryCategoryTranslation($parent, $dto, $container);

                $result[] = $parent;
            }
        }

        return $result;
    }

    /**
     * Transform categoryTranslation entities (ERM: many).
     * Relates: category_id = category.id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     */
    protected function transformCategoryCategoryTranslation(array &$parent, array $dto, array &$container): void
    {
        foreach ($container['categoryTranslation'] as $index => $categoryTranslation) {
            if (is_numeric($index) && $categoryTranslation['category_id'] == $dto['category']['id']) {
                $parent['categoryTranslation'][] = $categoryTranslation;
            }
        }
    }
}
