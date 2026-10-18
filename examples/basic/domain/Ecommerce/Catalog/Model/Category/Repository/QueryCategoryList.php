<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Repository;

use Ecommerce\Catalog\Model\Category\Query\CategoryListFilter;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
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
     * @throws \RuntimeException
     * @throws Throwable
     */
    public function __invoke(CategoryListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(CategoryEntity::SOURCE, 'category')
            ->select(
                'category.identifier AS identifier, category.slug AS slug, '
                . 'category.parent_identifier AS parentIdentifier, category.name AS categoryName, '
                . 'category.description AS description, category.meta_title AS metaTitle, '
                . 'category.meta_description AS metaDescription, category.is_active AS isActive, '
                . 'category.is_visible AS isVisible, category.sort_order AS sortOrder, '
                . 'category.product_count AS productCount, category.created_at AS createdAt, '
                . 'category.updated_at AS updatedAt, COUNT(*) OVER() AS total_count'
            );

        $hasId = $filter->id !== null;
        $hasIdentifier = $filter->identifier !== null;
        $hasSlug = $filter->slug !== null;
        $hasParentIdentifier = $filter->parentIdentifier !== null;
        $hasCategoryName = $filter->categoryName !== null;
        $hasDescription = $filter->description !== null;
        $hasMetaTitle = $filter->metaTitle !== null;
        $hasMetaDescription = $filter->metaDescription !== null;
        $hasIsActive = $filter->isActive !== null;
        $hasIsVisible = $filter->isVisible !== null;
        $hasSortOrder = $filter->sortOrder !== null;
        $hasProductCount = $filter->productCount !== null;
        $hasCreatedAtFrom = $filter->createdAtFrom !== null;
        $hasCreatedAtUntil = $filter->createdAtUntil !== null;
        $hasUpdatedAtFrom = $filter->updatedAtFrom !== null;
        $hasUpdatedAtUntil = $filter->updatedAtUntil !== null;

        if ($hasId) {
            $query = $query->and('category.id')->equals($filter->id);
        }
        if ($hasIdentifier) {
            $query = $query->and('category.identifier')->equals($filter->identifier);
        }
        if ($hasSlug) {
            $query = $query->and('category.slug')->equals($filter->slug);
        }
        if ($hasParentIdentifier) {
            $query = $query->and('category.parent_identifier')->equals($filter->parentIdentifier);
        }
        if ($hasCategoryName) {
            $query = $query->and('category.name')->equals($filter->categoryName);
        }
        if ($hasDescription) {
            $query = $query->and('category.description')->equals($filter->description);
        }
        if ($hasMetaTitle) {
            $query = $query->and('category.meta_title')->equals($filter->metaTitle);
        }
        if ($hasMetaDescription) {
            $query = $query->and('category.meta_description')->equals($filter->metaDescription);
        }
        if ($hasIsActive) {
            $query = $query->and('category.is_active')->equals($filter->isActive);
        }
        if ($hasIsVisible) {
            $query = $query->and('category.is_visible')->equals($filter->isVisible);
        }
        if ($hasSortOrder) {
            $query = $query->and('category.sort_order')->equals($filter->sortOrder);
        }
        if ($hasProductCount) {
            $query = $query->and('category.product_count')->equals($filter->productCount);
        }
        if ($hasCreatedAtFrom) {
            $query = $query->and('category.created_at')->greaterEquals($filter->createdAtFrom);
        }
        if ($hasCreatedAtUntil) {
            $query = $query->and('category.created_at')->lowerEquals($filter->createdAtUntil);
        }
        if ($hasUpdatedAtFrom) {
            $query = $query->and('category.updated_at')->greaterEquals($filter->updatedAtFrom);
        }
        if ($hasUpdatedAtUntil) {
            $query = $query->and('category.updated_at')->lowerEquals($filter->updatedAtUntil);
        }

        $query = $query
            ->orderBy('category.created_at', 'DESC')
            ->orderBy('category.id', 'ASC')
            ->limit(min($filter->limit, 20), $filter->offset);

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
