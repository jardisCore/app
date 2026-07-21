<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Repository;

use Ecommerce\Catalog\Aggregate\Product\Query\ProductVariantListFilter;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use RuntimeException;
use Throwable;
use Ecommerce\Catalog\Entity\ProductVariant as ProductVariantEntity;

/**
 * List query for ProductVariantList.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryProductVariantList extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(ProductVariantListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(ProductVariantEntity::SOURCE, 'productVariant')
            ->select('productVariant.id AS id, productVariant.identifier AS identifier, productVariant.sku AS productVariantSku, productVariant.variant_name AS variantName, productVariant.option_name AS optionName, productVariant.option_value AS optionValue, productVariant.price_modifier AS priceModifier, productVariant.stock_quantity AS stockQuantity, productVariant.is_available AS isAvailable, productVariant.sort_order AS sortOrder, product.sku AS productSku, COUNT(*) OVER() AS total_count')
            ->leftJoin('products', 'product.id = productVariant.product_id', 'product');

        $query = $query->where('product.sku')->equals($filter->productSku);

        $query = $query
            ->orderBy('productVariant.sort_order', 'ASC')
            ->limit($filter->limit, $filter->offset);

        return $this->handle(Repository::class, $this->resolveConnection())->findByQuery($query);
    }

    /**
     * Resolves the database connection to a PDO instance.
     *
     * Handles ConnectionPoolInterface (read/write splitting) and plain PDO.
     *
     * @throws \RuntimeException If no database connection is configured
     */
    protected function resolveConnection(): \PDO
    {
        $connection = $this->resource()->dbConnection();

        if ($connection instanceof \PDO) {
            return $connection;
        }

        if ($connection instanceof ConnectionPoolInterface) {
            return $connection->getReader()->pdo();
        }

        throw new \RuntimeException('No database connection configured');
    }
}
