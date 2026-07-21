<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Query\Handler\GetOrderByIdHandler;
use Ecommerce\Sales\Aggregate\Order\Query\Handler\GetOrderByIdsHandler;
use Ecommerce\Sales\Aggregate\Order\Query\Handler\GetOrderByOrderNumberHandler;
use Ecommerce\Sales\Aggregate\Order\Query\Handler\GetOrderDetailListHandler;
use Ecommerce\Sales\Aggregate\Order\Query\Handler\GetOrderItemListHandler;
use Ecommerce\Sales\Aggregate\Order\Query\Handler\GetOrderListHandler;
use Ecommerce\Sales\Aggregate\Order\Query\OrderDetailListFilter;
use Ecommerce\Sales\Aggregate\Order\Query\OrderItemListFilter;
use Ecommerce\Sales\Aggregate\Order\Query\OrderListFilter;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Query\OrderById as QueryOrderById;
use Ecommerce\Sales\Aggregate\Order\Query\OrderByIds as QueryOrderByIds;
use Ecommerce\Sales\Aggregate\Order\Query\OrderByOrderNumber as QueryOrderByOrderNumber;

/**
 * Order Aggregate Read Facade.
 *
 * Thin read-only delegator (G9) — hosts the inline query/list
 * operations for this aggregate. No write access; see the sibling
 * write facade in the same directory for commands + event().
 */
class OrderRead extends EcommerceContext
{
    /**
     * Gets OrderById aggregate.
     *
     * @param QueryOrderById $orderById Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getOrderById(QueryOrderById $orderById, string $version = ''): DomainResponseInterface
    {
        return $this->context(GetOrderByIdHandler::class, $orderById, $version)();
    }

    /**
     * Gets OrderByIds aggregate.
     *
     * @param QueryOrderByIds $orderByIds Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getOrderByIds(QueryOrderByIds $orderByIds, string $version = ''): DomainResponseInterface
    {
        return $this->context(GetOrderByIdsHandler::class, $orderByIds, $version)();
    }

    /**
     * Gets OrderByOrderNumber aggregate.
     *
     * @param QueryOrderByOrderNumber $orderByOrderNumber Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getOrderByOrderNumber(
        QueryOrderByOrderNumber $orderByOrderNumber,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(GetOrderByOrderNumberHandler::class, $orderByOrderNumber, $version)();
    }

    /**
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function orderDetailList(OrderDetailListFilter $filter, string $version = ''): array
    {
        return $this->context(GetOrderDetailListHandler::class, $filter, $version)();
    }

    /**
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function orderItemList(OrderItemListFilter $filter, string $version = ''): array
    {
        return $this->context(GetOrderItemListHandler::class, $filter, $version)();
    }

    /**
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function orderList(OrderListFilter $filter, string $version = ''): array
    {
        return $this->context(GetOrderListHandler::class, $filter, $version)();
    }
}
