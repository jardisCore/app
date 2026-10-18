<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Repository;

use Ecommerce\Catalog\Model\Product\Query\ProductListFilter;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
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
     * @throws \RuntimeException
     * @throws Throwable
     */
    public function __invoke(ProductListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(ProductEntity::SOURCE, 'product')
            ->select(
                'product.identifier AS identifier, product.sku AS sku, product.name AS productName, '
                . 'product.slug AS slug, product.description AS description, '
                . 'product.short_description AS shortDescription, product.price AS price, '
                . 'product.compare_at_price AS compareAtPrice, product.cost_price AS costPrice, '
                . 'product.currency AS currency, product.weight_grams AS weightGrams, product.is_active AS isActive, '
                . 'product.is_featured AS isFeatured, product.tax_class AS taxClass, product.created_at AS createdAt, '
                . 'product.updated_at AS updatedAt, COUNT(*) OVER() AS total_count'
            );

        $hasId = $filter->id !== null;
        $hasIdentifier = $filter->identifier !== null;
        $hasSku = $filter->sku !== null;
        $hasProductName = $filter->productName !== null;
        $hasSlug = $filter->slug !== null;
        $hasDescription = $filter->description !== null;
        $hasShortDescription = $filter->shortDescription !== null;
        $hasPrice = $filter->price !== null;
        $hasCompareAtPrice = $filter->compareAtPrice !== null;
        $hasCostPrice = $filter->costPrice !== null;
        $hasCurrency = $filter->currency !== null;
        $hasWeightGrams = $filter->weightGrams !== null;
        $hasIsActive = $filter->isActive !== null;
        $hasIsFeatured = $filter->isFeatured !== null;
        $hasTaxClass = $filter->taxClass !== null;
        $hasCreatedAtFrom = $filter->createdAtFrom !== null;
        $hasCreatedAtUntil = $filter->createdAtUntil !== null;
        $hasUpdatedAtFrom = $filter->updatedAtFrom !== null;
        $hasUpdatedAtUntil = $filter->updatedAtUntil !== null;

        if ($hasId) {
            $query = $query->and('product.id')->equals($filter->id);
        }
        if ($hasIdentifier) {
            $query = $query->and('product.identifier')->equals($filter->identifier);
        }
        if ($hasSku) {
            $query = $query->and('product.sku')->equals($filter->sku);
        }
        if ($hasProductName) {
            $query = $query->and('product.name')->equals($filter->productName);
        }
        if ($hasSlug) {
            $query = $query->and('product.slug')->equals($filter->slug);
        }
        if ($hasDescription) {
            $query = $query->and('product.description')->equals($filter->description);
        }
        if ($hasShortDescription) {
            $query = $query->and('product.short_description')->equals($filter->shortDescription);
        }
        if ($hasPrice) {
            $query = $query->and('product.price')->equals($filter->price);
        }
        if ($hasCompareAtPrice) {
            $query = $query->and('product.compare_at_price')->equals($filter->compareAtPrice);
        }
        if ($hasCostPrice) {
            $query = $query->and('product.cost_price')->equals($filter->costPrice);
        }
        if ($hasCurrency) {
            $query = $query->and('product.currency')->equals($filter->currency);
        }
        if ($hasWeightGrams) {
            $query = $query->and('product.weight_grams')->equals($filter->weightGrams);
        }
        if ($hasIsActive) {
            $query = $query->and('product.is_active')->equals($filter->isActive);
        }
        if ($hasIsFeatured) {
            $query = $query->and('product.is_featured')->equals($filter->isFeatured);
        }
        if ($hasTaxClass) {
            $query = $query->and('product.tax_class')->equals($filter->taxClass);
        }
        if ($hasCreatedAtFrom) {
            $query = $query->and('product.created_at')->greaterEquals($filter->createdAtFrom);
        }
        if ($hasCreatedAtUntil) {
            $query = $query->and('product.created_at')->lowerEquals($filter->createdAtUntil);
        }
        if ($hasUpdatedAtFrom) {
            $query = $query->and('product.updated_at')->greaterEquals($filter->updatedAtFrom);
        }
        if ($hasUpdatedAtUntil) {
            $query = $query->and('product.updated_at')->lowerEquals($filter->updatedAtUntil);
        }

        $query = $query
            ->orderBy('product.created_at', 'DESC')
            ->orderBy('product.id', 'ASC')
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
