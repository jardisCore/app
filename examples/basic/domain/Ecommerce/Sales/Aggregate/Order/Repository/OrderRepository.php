<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Order as OrderAggregate;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Order as OrderHandler;
use Ecommerce\Sales\Aggregate\Order\Query\OrderById as OrderByIdQuery;
use Ecommerce\Sales\Aggregate\Order\Query\OrderByIds as OrderByIdsQuery;
use Ecommerce\Sales\Aggregate\Order\Query\OrderByOrderNumber as OrderByOrderNumberQuery;
use Ecommerce\Sales\Aggregate\Order\Query\OrderDetailListFilter;
use Ecommerce\Sales\Aggregate\Order\Query\OrderItemListFilter;
use Ecommerce\Sales\Aggregate\Order\Query\OrderListFilter;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;

/**
 * Repository for Order aggregate.
 *
 * Orchestrates Query → Transform → Handler process.
 * Optionally uses Hydration for entity hydration.
 */
class OrderRepository extends EcommerceContext
{
    /**
     * Get Order aggregate.
     *
     * @param OrderByIdQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return OrderHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getOrderById(OrderByIdQuery $query, bool $asHandler = true): OrderHandler|array|null
    {
        $rootContainer = $this->handle(QueryOrderById::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Order aggregate.
     *
     * @param OrderByIdsQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return OrderHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getOrderByIds(OrderByIdsQuery $query, bool $asHandler = true): OrderHandler|array|null
    {
        $rootContainer = $this->handle(QueryOrderByIds::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Get Order aggregate.
     *
     * @param OrderByOrderNumberQuery $query Query parameters
     * @param bool $asHandler If true, return handler with hydrated aggregate; if false, return array
     * @return OrderHandler|array|null Handler with aggregate, nested array, or null if not found
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getOrderByOrderNumber(OrderByOrderNumberQuery $query, bool $asHandler = true): OrderHandler|array|null
    {
        $rootContainer = $this->handle(QueryOrderByOrderNumber::class)($query);

        return $this->buildAggregate($rootContainer, $asHandler);
    }

    /**
     * Build Order aggregate from container data.
     *
     * @param array $rootContainer Root container with root entity data
     * @param bool $asHandler If true, return handler; if false, return array
     * @return OrderHandler|array|null
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function buildAggregate(array $rootContainer, bool $asHandler = true): OrderHandler|array|null
    {
        $container = $this->handle(QueryOrderAdditions::class)($rootContainer);
        $aggregateArray = $this->handle(TransformOrder::class)($container);

        if (empty($aggregateArray)) {
            return $asHandler ? null : [];
        }

        if (!$asHandler) {
            return $aggregateArray;
        }

        $hydration = $this->handle(Hydration::class);
        $aggregateEntity = $hydration->hydrateAggregate(
            $this->handle(OrderAggregate::class),
            $aggregateArray[0]
        );

        return $this->handle(OrderHandler::class, $aggregateEntity);
    }

    /**
     * Creates a new empty Order aggregate handler.
     *
     * Use this for CREATE operations where no existing aggregate exists.
     *
     * @return OrderHandler Handler with empty aggregate
     * @throws Throwable
     */
    public function createNew(): OrderHandler
    {
        $aggregateEntity = $this->handle(OrderAggregate::class);

        return $this->handle(OrderHandler::class, $aggregateEntity);
    }
    /**
     * Persists the Order aggregate.
     *
     * Validates all OWNED entities before persistence.
     *
     * @param OrderHandler $handler The aggregate handler
     * @return ContextResponseInterface Response with events
     * @throws Throwable
     */
    public function persist(OrderHandler $handler): ContextResponseInterface
    {
        $this->handle(ValidateOrder::class)($handler);

        return $this->handle(PersistOrder::class)($handler);
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
    public function orderDetailList(OrderDetailListFilter $filter): array
    {
        return $this->listResponse($this->handle(QueryOrderDetailList::class)($filter), $filter->limit, $filter->offset);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function orderItemList(OrderItemListFilter $filter): array
    {
        return $this->listResponse($this->handle(QueryOrderItemList::class)($filter), $filter->limit, $filter->offset);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function orderList(OrderListFilter $filter): array
    {
        return $this->listResponse($this->handle(QueryOrderList::class)($filter), $filter->limit, $filter->offset);
    }
}
