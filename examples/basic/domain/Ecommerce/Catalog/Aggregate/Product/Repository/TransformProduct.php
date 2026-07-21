<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Repository;

use Ecommerce\EcommerceContext;

/**
 * Transforms flat query results into nested Product structure.
 *
 * Input: Arrays grouped by entity name (from query results)
 * Output: Nested array structure matching aggregate definition
 */
class TransformProduct extends EcommerceContext
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

        foreach ($container['product'] as $index => $product) {
            if (is_numeric($index) && !isset($processedIds[$product['id']])) {
                $processedIds[$product['id']] = true;
                $parent = $dto['product'] = $product;

                $this->transformProductProductImage($parent, $dto, $container);
                $this->transformProductProductVariant($parent, $dto, $container);

                $result[] = $parent;
            }
        }

        return $result;
    }

    /**
     * Transform productImage entities (ERM: many).
     * Relates: product_id = product.id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     */
    protected function transformProductProductImage(array &$parent, array $dto, array &$container): void
    {
        foreach ($container['productImage'] as $index => $productImage) {
            if (is_numeric($index) && $productImage['product_id'] == $dto['product']['id']) {
                $parent['productImage'][] = $productImage;
            }
        }
    }
    /**
     * Transform productVariant entities (ERM: many).
     * Relates: product_id = product.id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     */
    protected function transformProductProductVariant(array &$parent, array $dto, array &$container): void
    {
        foreach ($container['productVariant'] as $index => $productVariant) {
            if (is_numeric($index) && $productVariant['product_id'] == $dto['product']['id']) {
                $dto['productVariant'] = $productVariant;

                $this->transformProductVariantProductVariantPrice($productVariant, $dto, $container);

                $parent['productVariant'][] = $productVariant;
            }
        }
    }
    /**
     * Transform productVariantPrice entities (ERM: many).
     * Relates: variant_id = productVariant.id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     */
    protected function transformProductVariantProductVariantPrice(array &$parent, array $dto, array &$container): void
    {
        foreach ($container['productVariantPrice'] as $index => $productVariantPrice) {
            if (is_numeric($index) && $productVariantPrice['variant_id'] == $dto['productVariant']['id']) {
                $parent['productVariantPrice'][] = $productVariantPrice;
            }
        }
    }
}
