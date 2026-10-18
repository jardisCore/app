<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Query\Handler;

use Ecommerce\Catalog\Model\Product\Query\Response\ProductResponse;
use Ecommerce\Catalog\Model\Product\Repository\ProductRepository;
use Ecommerce\EcommerceContext;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Data\FieldMapper;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifiers as QueryProductByIdentifiers;

/**
 * Query endpoint: GetProductByIdentifiers
 */
class GetProductByIdentifiersHandler extends EcommerceContext
{
    private const BULK_WARN_ABOVE = 100;

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
        /** @var QueryProductByIdentifiers $productByIdentifiers */
        $productByIdentifiers = $this->payload();

        $logger = $this->resource()->logger();
        if ($logger !== null) {
            $count = count($productByIdentifiers->identifiers);
            if ($count > self::BULK_WARN_ABOVE) {
                $logger->warning('Bulk read {handler} received {count} keys (threshold {threshold}).', [
                    'handler' => self::class,
                    'count' => $count,
                    'threshold' => self::BULK_WARN_ABOVE,
                ]);
            }
        }

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->handle(ProductRepository::class)->getProductByIdentifiers($productByIdentifiers, false);

        /** @var array<string, array<string, string>> $readMaps */
        $readMaps = [
            'product' => [
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
                'updatedAt' => 'updated_at',
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

        $wireTypes = [
            'createdAt' => 'date-time',
            'updatedAt' => 'date-time',
            'productVariant' => [
                'createdAt' => 'date-time',
                'productVariantPrice' => [
                    'validFrom' => 'date',
                    'validUntil' => 'date',
                ],
            ],
            'productImage' => [
                'updatedAt' => 'date-time',
            ],
        ];

        $projectWire = $this->handle(ProjectWire::class);
        $fieldMapper = $this->handle(FieldMapper::class);
        $hydration = $this->handle(Hydration::class);

        /** @var array<int, ProductResponse> $projected */
        $projected = array_map(
            function (array $row) use (
                $mapEntity,
                $fieldMapper,
                $hydration,
                $projectWire,
                $wireTypes
            ): ProductResponse {
                $mapped = $fieldMapper->fromAggregate($row, $mapEntity, 'product');
                $mapped = $projectWire($mapped, $wireTypes);
                $productResponse = new ProductResponse();
                $hydration->hydrateAggregate($productResponse, $mapped);
                return $productResponse;
            },
            $result,
        );

        $this->result()->setData(['@type' => 'productList', 'items' => $projected]);

        return ($this->transformer())($this->result());
    }
}
