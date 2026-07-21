<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Repository;

use Ecommerce\Catalog\Aggregate\Category\Query\CategoryTranslationListFilter;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use RuntimeException;
use Throwable;
use Ecommerce\Catalog\Entity\CategoryTranslation as CategoryTranslationEntity;

/**
 * List query for CategoryTranslationList.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryCategoryTranslationList extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(CategoryTranslationListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(CategoryTranslationEntity::SOURCE, 'categoryTranslation')
            ->select('categoryTranslation.id AS id, categoryTranslation.locale AS locale, categoryTranslation.title AS title, categoryTranslation.description AS description, categoryTranslation.meta_title AS metaTitle, categoryTranslation.meta_description AS metaDescription, category.slug AS slug, COUNT(*) OVER() AS total_count')
            ->leftJoin('categories', 'category.id = categoryTranslation.category_id', 'category');

        $query = $query->where('category.slug')->equals($filter->categorySlug);

        $query = $query
            ->orderBy('categoryTranslation.locale', 'ASC')
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
