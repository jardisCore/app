<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product;

use Ecommerce\Catalog\Model\Product\Query\Handler\GetProductByIdHandler;
use Ecommerce\Catalog\Model\Product\Query\Handler\GetProductByIdentifierHandler;
use Ecommerce\Catalog\Model\Product\Query\Handler\GetProductByIdentifiersHandler;
use Ecommerce\Catalog\Model\Product\Query\Handler\GetProductByIdsHandler;
use Ecommerce\Catalog\Model\Product\Query\Handler\GetProductListHandler;
use Ecommerce\Catalog\Model\Product\Query\ProductListFilter;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Workflow\Data\ReadResult;
use Throwable;
use Ecommerce\Catalog\Model\Product\Query\ProductById as QueryProductById;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifier as QueryProductByIdentifier;
use Ecommerce\Catalog\Model\Product\Query\ProductByIdentifiers as QueryProductByIdentifiers;
use Ecommerce\Catalog\Model\Product\Query\ProductByIds as QueryProductByIds;

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
     * Gets ProductByIdentifiers aggregate.
     *
     * @param QueryProductByIdentifiers $productByIdentifiers Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getProductByIdentifiers(
        QueryProductByIdentifiers $productByIdentifiers,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(GetProductByIdentifiersHandler::class, $productByIdentifiers, $version)();
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
     * Declared query `productList` (Queries.json, visibility public).
     *
     * @param ProductListFilter $filter Declared query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function productList(ProductListFilter $filter, string $version = ''): ReadResult
    {
        return $this->context(GetProductListHandler::class, $filter, $version)();
    }
}
