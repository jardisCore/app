<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Query\Handler\GetOrderByIdHandler;
use Ecommerce\Sales\Model\Order\Query\Handler\GetOrderByIdsHandler;
use Ecommerce\Sales\Model\Order\Query\Handler\GetOrderByOrderNumberHandler;
use Ecommerce\Sales\Model\Order\Query\Handler\GetOrderByOrderNumbersHandler;
use Ecommerce\Sales\Model\Order\Query\Handler\GetOrderListHandler;
use Ecommerce\Sales\Model\Order\Query\OrderListFilter;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Workflow\Data\ReadResult;
use Throwable;
use Ecommerce\Sales\Model\Order\Query\OrderById as QueryOrderById;
use Ecommerce\Sales\Model\Order\Query\OrderByIds as QueryOrderByIds;
use Ecommerce\Sales\Model\Order\Query\OrderByOrderNumber as QueryOrderByOrderNumber;
use Ecommerce\Sales\Model\Order\Query\OrderByOrderNumbers as QueryOrderByOrderNumbers;

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
     * Gets OrderByOrderNumbers aggregate.
     *
     * @param QueryOrderByOrderNumbers $orderByOrderNumbers Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getOrderByOrderNumbers(
        QueryOrderByOrderNumbers $orderByOrderNumbers,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(GetOrderByOrderNumbersHandler::class, $orderByOrderNumbers, $version)();
    }

    /**
     * Declared query `orderList` (Queries.json, visibility public).
     *
     * @param OrderListFilter $filter Declared query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function orderList(OrderListFilter $filter, string $version = ''): ReadResult
    {
        return $this->context(GetOrderListHandler::class, $filter, $version)();
    }
}
