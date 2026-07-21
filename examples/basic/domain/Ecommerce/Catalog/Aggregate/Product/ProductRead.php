<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product;

use Ecommerce\Catalog\Aggregate\Product\Query\Handler\GetProductByIdHandler;
use Ecommerce\Catalog\Aggregate\Product\Query\Handler\GetProductByIdentifierHandler;
use Ecommerce\Catalog\Aggregate\Product\Query\Handler\GetProductByIdsHandler;
use Ecommerce\Catalog\Aggregate\Product\Query\Handler\GetProductListHandler;
use Ecommerce\Catalog\Aggregate\Product\Query\Handler\GetProductVariantListHandler;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductListFilter;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductVariantListFilter;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductById as QueryProductById;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductByIdentifier as QueryProductByIdentifier;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductByIds as QueryProductByIds;

/**
 * Product Aggregate Read Facade.
 *
 * Thin read-only delegator (G9) — hosts the inline query/list
 * operations for this aggregate. No write access; see the sibling
 * write facade in the same directory for commands + event().
 */
class ProductRead extends EcommerceContext
{
    /**
     * Gets ProductById aggregate.
     *
     * @param QueryProductById $productById Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getProductById(QueryProductById $productById, string $version = ''): DomainResponseInterface
    {
        return $this->context(GetProductByIdHandler::class, $productById, $version)();
    }

    /**
     * Gets ProductByIdentifier aggregate.
     *
     * @param QueryProductByIdentifier $productByIdentifier Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getProductByIdentifier(
        QueryProductByIdentifier $productByIdentifier,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(GetProductByIdentifierHandler::class, $productByIdentifier, $version)();
    }

    /**
     * Gets ProductByIds aggregate.
     *
     * @param QueryProductByIds $productByIds Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getProductByIds(QueryProductByIds $productByIds, string $version = ''): DomainResponseInterface
    {
        return $this->context(GetProductByIdsHandler::class, $productByIds, $version)();
    }

    /**
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function productList(ProductListFilter $filter, string $version = ''): array
    {
        return $this->context(GetProductListHandler::class, $filter, $version)();
    }

    /**
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function productVariantList(ProductVariantListFilter $filter, string $version = ''): array
    {
        return $this->context(GetProductVariantListHandler::class, $filter, $version)();
    }
}
