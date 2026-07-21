<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category;

use Ecommerce\Catalog\Aggregate\Category\Query\CategoryListFilter;
use Ecommerce\Catalog\Aggregate\Category\Query\CategoryTranslationListFilter;
use Ecommerce\Catalog\Aggregate\Category\Query\Handler\GetCategoryByIdHandler;
use Ecommerce\Catalog\Aggregate\Category\Query\Handler\GetCategoryByIdentifierHandler;
use Ecommerce\Catalog\Aggregate\Category\Query\Handler\GetCategoryByIdsHandler;
use Ecommerce\Catalog\Aggregate\Category\Query\Handler\GetCategoryListHandler;
use Ecommerce\Catalog\Aggregate\Category\Query\Handler\GetCategoryTranslationListHandler;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Throwable;
use Ecommerce\Catalog\Aggregate\Category\Query\CategoryById as QueryCategoryById;
use Ecommerce\Catalog\Aggregate\Category\Query\CategoryByIdentifier as QueryCategoryByIdentifier;
use Ecommerce\Catalog\Aggregate\Category\Query\CategoryByIds as QueryCategoryByIds;

/**
 * Category Aggregate Read Facade.
 *
 * Thin read-only delegator (G9) — hosts the inline query/list
 * operations for this aggregate. No write access; see the sibling
 * write facade in the same directory for commands + event().
 */
class CategoryRead extends EcommerceContext
{
    /**
     * Gets CategoryById aggregate.
     *
     * @param QueryCategoryById $categoryById Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getCategoryById(QueryCategoryById $categoryById, string $version = ''): DomainResponseInterface
    {
        return $this->context(GetCategoryByIdHandler::class, $categoryById, $version)();
    }

    /**
     * Gets CategoryByIdentifier aggregate.
     *
     * @param QueryCategoryByIdentifier $categoryByIdentifier Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getCategoryByIdentifier(
        QueryCategoryByIdentifier $categoryByIdentifier,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(GetCategoryByIdentifierHandler::class, $categoryByIdentifier, $version)();
    }

    /**
     * Gets CategoryByIds aggregate.
     *
     * @param QueryCategoryByIds $categoryByIds Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getCategoryByIds(QueryCategoryByIds $categoryByIds, string $version = ''): DomainResponseInterface
    {
        return $this->context(GetCategoryByIdsHandler::class, $categoryByIds, $version)();
    }

    /**
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function categoryList(CategoryListFilter $filter, string $version = ''): array
    {
        return $this->context(GetCategoryListHandler::class, $filter, $version)();
    }

    /**
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function categoryTranslationList(CategoryTranslationListFilter $filter, string $version = ''): array
    {
        return $this->context(GetCategoryTranslationListHandler::class, $filter, $version)();
    }
}
