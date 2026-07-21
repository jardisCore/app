<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Repository;

use Ecommerce\Catalog\Aggregate\Category\Aggregate\Category as CategoryHandler;
use Ecommerce\Catalog\Aggregate\Category\Aggregate\Entity\Category as CategoryAggregate;
use Ecommerce\Catalog\Aggregate\Category\Query\CategoryById as CategoryByIdQuery;
use Ecommerce\Catalog\Aggregate\Category\Query\CategoryByIdentifier as CategoryByIdentifierQuery;
use Ecommerce\Catalog\Aggregate\Category\Query\CategoryByIds as CategoryByIdsQuery;
use Ecommerce\Catalog\Aggregate\Category\Query\CategoryListFilter;
use Ecommerce\Catalog\Aggregate\Category\Query\CategoryTranslationListFilter;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;

/**
 * Repository for Category aggregate.
 *
 * Orchestrates Query → Transform → Handler process.
 * Optionally uses Hydration for entity hydration.
 */
class CategoryRepository extends EcommerceContext
{
    /**
     * Get Category aggregate.
     *
     * @param CategoryByIdQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return CategoryHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getCategoryById(CategoryByIdQuery $query, bool $asHandler = true): CategoryHandler|array|null
    {
        $rootContainer = $this->handle(QueryCategoryById::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Category aggregate.
     *
     * @param CategoryByIdentifierQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return CategoryHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getCategoryByIdentifier(CategoryByIdentifierQuery $query, bool $asHandler = true): CategoryHandler|array|null
    {
        $rootContainer = $this->handle(QueryCategoryByIdentifier::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Category aggregate.
     *
     * @param CategoryByIdsQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return CategoryHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getCategoryByIds(CategoryByIdsQuery $query, bool $asHandler = true): CategoryHandler|array|null
    {
        $rootContainer = $this->handle(QueryCategoryByIds::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Build Category aggregate from container data.
     *
     * @param array $rootContainer Root container with root entity data
     * @param bool $asHandler If true, return handler; if false, return array
     * @return CategoryHandler|array|null
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function buildAggregate(array $rootContainer, bool $asHandler = true): CategoryHandler|array|null
    {
        $container = $this->handle(QueryCategoryAdditions::class)($rootContainer);
        $aggregateArray = $this->handle(TransformCategory::class)($container);

        if (empty($aggregateArray)) {
            return $asHandler ? null : [];
        }

        if (!$asHandler) {
            return $aggregateArray;
        }

        $hydration = $this->handle(Hydration::class);
        $aggregateEntity = $hydration->hydrateAggregate(
            $this->handle(CategoryAggregate::class),
            $aggregateArray[0]
        );

        return $this->handle(CategoryHandler::class, $aggregateEntity);
    }

    /**
     * Creates a new empty Category aggregate handler.
     *
     * Use this for CREATE operations where no existing aggregate exists.
     *
     * @return CategoryHandler Handler with empty aggregate
     * @throws Throwable
     */
    public function createNew(): CategoryHandler
    {
        $aggregateEntity = $this->handle(CategoryAggregate::class);

        return $this->handle(CategoryHandler::class, $aggregateEntity);
    }
    /**
     * Persists the Category aggregate.
     *
     * Validates all OWNED entities before persistence.
     *
     * @param CategoryHandler $handler The aggregate handler
     * @return ContextResponseInterface Response with events
     * @throws Throwable
     */
    public function persist(CategoryHandler $handler): ContextResponseInterface
    {
        $this->handle(ValidateCategory::class)($handler);

        return $this->handle(PersistCategory::class)($handler);
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
    public function categoryList(CategoryListFilter $filter): array
    {
        return $this->listResponse($this->handle(QueryCategoryList::class)($filter), $filter->limit, $filter->offset);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function categoryTranslationList(CategoryTranslationListFilter $filter): array
    {
        return $this->listResponse($this->handle(QueryCategoryTranslationList::class)($filter), $filter->limit, $filter->offset);
    }
}
