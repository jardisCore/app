<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Repository;

use Ecommerce\Catalog\Aggregate\Category\Query\CategoryListFilter;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use RuntimeException;
use Throwable;
use Ecommerce\Catalog\Entity\Category as CategoryEntity;

/**
 * List query for CategoryList.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryCategoryList extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(CategoryListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(CategoryEntity::SOURCE, 'category')
            ->select('category.id AS id, category.identifier AS identifier, category.slug AS slug, category.parent_identifier AS parentIdentifier, category.name AS categoryName, category.is_active AS isActive, category.is_visible AS isVisible, category.sort_order AS sortOrder, category.product_count AS productCount, COUNT(*) OVER() AS total_count');

        if ($filter->isActive !== null) {
            $query = $query->where('category.is_active')->equals($filter->isActive);
        }

        $query = $query
            ->orderBy('category.sort_order', 'ASC')
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
