<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Query\Handler;

use Ecommerce\Catalog\Aggregate\Product\Query\Response\ProductResponse;
use Ecommerce\Catalog\Aggregate\Product\Repository\ProductRepository;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Data\FieldMapper;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductByIds as QueryProductByIds;

/**
 * Query endpoint: GetProductByIds
 */
class GetProductByIdsHandler extends EcommerceContext
{
    /**
     * Executes the Query endpoint operation.
     *
     * @return DomainResponseInterface
     * @throws Throwable
     * @throws Exception
     * @throws ReflectionException
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var QueryProductByIds $productByIds */
        $productByIds = $this->payload();

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->handle(ProductRepository::class)->getProductByIds($productByIds, false);

        /** @var array<string, array<string, string>> $readMaps */
        $readMaps = [
            'product' => [
                'id' => 'id',
                'identifier' => 'identifier',
                'sku' => 'sku',
                'productName' => 'name',
                'slug' => 'slug',
                'description' => 'description',
                'shortDescription' => 'short_description',
                'price' => 'price',
                'compareAtPrice' => 'compare_at_price',
                'costPrice' => 'cost_price',
                'currency' => 'currency',
                'weightGrams' => 'weight_grams',
                'isActive' => 'is_active',
                'isFeatured' => 'is_featured',
                'taxClass' => 'tax_class',
                'createdAt' => 'created_at',
                'updatedAt' => 'updated_at',
            ],
            'productImage' => [
                'id' => 'id',
                'url' => 'url',
                'altText' => 'alt_text',
                'mimeType' => 'mime_type',
                'fileSize' => 'file_size',
                'width' => 'width',
                'height' => 'height',
                'sortOrder' => 'sort_order',
                'isPrimary' => 'is_primary',
            ],
            'productVariant' => [
                'identifier' => 'identifier',
                'sku' => 'sku',
                'variantName' => 'variant_name',
                'optionName' => 'option_name',
                'optionValue' => 'option_value',
                'priceModifier' => 'price_modifier',
                'stockQuantity' => 'stock_quantity',
                'lowStockThreshold' => 'low_stock_threshold',
                'weightGrams' => 'weight_grams',
                'isAvailable' => 'is_available',
                'sortOrder' => 'sort_order',
                'createdAt' => 'created_at',
            ],
            'productVariantPrice' => [
                'id' => 'id',
                'region' => 'region',
                'price' => 'price',
                'currency' => 'currency',
                'validFrom' => 'valid_from',
                'validUntil' => 'valid_until',
            ],
        ];
        $mapEntity = static fn(string $entity): array => $readMaps[$entity] ?? [];

        /** @var array<int, ProductResponse> $projected */
        $projected = array_map(
            function (array $row) use ($mapEntity): ProductResponse {
                $mapped = $this->handle(FieldMapper::class)->fromAggregate($row, $mapEntity, 'product');
                $productResponse = new ProductResponse();
                $this->handle(Hydration::class)->hydrateAggregate($productResponse, $mapped);
                return $productResponse;
            },
            $result,
        );

        $this->result()->setData(['product' => $projected]);

        return $this->handle(DomainResponseTransformer::class)->transform($this->result());
    }
}
