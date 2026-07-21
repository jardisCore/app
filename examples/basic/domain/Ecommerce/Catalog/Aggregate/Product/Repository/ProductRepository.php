<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Repository;

use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\Product as ProductAggregate;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Product as ProductHandler;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductById as ProductByIdQuery;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductByIdentifier as ProductByIdentifierQuery;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductByIds as ProductByIdsQuery;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductListFilter;
use Ecommerce\Catalog\Aggregate\Product\Query\ProductVariantListFilter;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;

/**
 * Repository for Product aggregate.
 *
 * Orchestrates Query → Transform → Handler process.
 * Optionally uses Hydration for entity hydration.
 */
class ProductRepository extends EcommerceContext
{
    /**
     * Get Product aggregate.
     *
     * @param ProductByIdQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return ProductHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getProductById(ProductByIdQuery $query, bool $asHandler = true): ProductHandler|array|null
    {
        $rootContainer = $this->handle(QueryProductById::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Product aggregate.
     *
     * @param ProductByIdentifierQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return ProductHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getProductByIdentifier(ProductByIdentifierQuery $query, bool $asHandler = true): ProductHandler|array|null
    {
        $rootContainer = $this->handle(QueryProductByIdentifier::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Product aggregate.
     *
     * @param ProductByIdsQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return ProductHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getProductByIds(ProductByIdsQuery $query, bool $asHandler = true): ProductHandler|array|null
    {
        $rootContainer = $this->handle(QueryProductByIds::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Build Product aggregate from container data.
     *
     * @param array $rootContainer Root container with root entity data
     * @param bool $asHandler If true, return handler; if false, return array
     * @return ProductHandler|array|null
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function buildAggregate(array $rootContainer, bool $asHandler = true): ProductHandler|array|null
    {
        $container = $this->handle(QueryProductAdditions::class)($rootContainer);
        $aggregateArray = $this->handle(TransformProduct::class)($container);

        if (empty($aggregateArray)) {
            return $asHandler ? null : [];
        }

        if (!$asHandler) {
            return $aggregateArray;
        }

        $hydration = $this->handle(Hydration::class);
        $aggregateEntity = $hydration->hydrateAggregate(
            $this->handle(ProductAggregate::class),
            $aggregateArray[0]
        );

        return $this->handle(ProductHandler::class, $aggregateEntity);
    }

    /**
     * Creates a new empty Product aggregate handler.
     *
     * Use this for CREATE operations where no existing aggregate exists.
     *
     * @return ProductHandler Handler with empty aggregate
     * @throws Throwable
     */
    public function createNew(): ProductHandler
    {
        $aggregateEntity = $this->handle(ProductAggregate::class);

        return $this->handle(ProductHandler::class, $aggregateEntity);
    }
    /**
     * Persists the Product aggregate.
     *
     * Validates all OWNED entities before persistence.
     *
     * @param ProductHandler $handler The aggregate handler
     * @return ContextResponseInterface Response with events
     * @throws Throwable
     */
    public function persist(ProductHandler $handler): ContextResponseInterface
    {
        $this->handle(ValidateProduct::class)($handler);

        return $this->handle(PersistProduct::class)($handler);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     */
    protected function listResponse(array $rows, int $limit, int $offset): array
    {
        $total = (int) ($rows[0]['total_count'] ?? 0);

        return [
            'items' => array_map(fn(array $row) => array_diff_key($row, ['total_count' => true]), $rows),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function productList(ProductListFilter $filter): array
    {
        return $this->listResponse($this->handle(QueryProductList::class)($filter), $filter->limit, $filter->offset);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function productVariantList(ProductVariantListFilter $filter): array
    {
        return $this->listResponse($this->handle(QueryProductVariantList::class)($filter), $filter->limit, $filter->offset);
    }
}
