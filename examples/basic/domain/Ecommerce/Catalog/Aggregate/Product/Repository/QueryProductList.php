<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Repository;

use Ecommerce\Catalog\Aggregate\Product\Query\ProductListFilter;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use RuntimeException;
use Throwable;
use Ecommerce\Catalog\Entity\Product as ProductEntity;

/**
 * List query for ProductList.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryProductList extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(ProductListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(ProductEntity::SOURCE, 'product')
            ->select('product.id AS id, product.identifier AS identifier, product.sku AS sku, product.name AS productName, product.slug AS slug, product.price AS price, product.compare_at_price AS compareAtPrice, product.currency AS currency, product.is_active AS isActive, product.is_featured AS isFeatured, product.tax_class AS taxClass, product.created_at AS createdAt, COUNT(*) OVER() AS total_count');

        if ($filter->taxClass !== null) {
            $query = $query->where('product.tax_class')->equals($filter->taxClass);
        }
        if ($filter->isActive !== null) {
            $query = $query->and('product.is_active')->equals($filter->isActive);
        }

        $query = $query
            ->orderBy('product.created_at', 'DESC')
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
