<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Repository;

use Ecommerce\EcommerceContext;

/**
 * Transforms flat query results into nested PriceTier structure.
 *
 * Input: Arrays grouped by entity name (from query results)
 * Output: Nested array structure matching aggregate definition
 */
class TransformPriceTier extends EcommerceContext
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

        foreach ($container['priceTier'] as $index => $priceTier) {
            if (is_numeric($index) && !isset($processedIds[$priceTier['id']])) {
                $processedIds[$priceTier['id']] = true;
                $parent = $priceTier;

                $result[] = $parent;
            }
        }

        return $result;
    }
}
