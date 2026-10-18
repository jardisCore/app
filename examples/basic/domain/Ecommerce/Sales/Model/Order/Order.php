<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Command\Handler\AddItemDiscount;
use Ecommerce\Sales\Model\Order\Command\Handler\AddOrderItem;
use Ecommerce\Sales\Model\Order\Command\Handler\CreateOrder;
use Ecommerce\Sales\Model\Order\Command\Handler\RemoveAddress;
use Ecommerce\Sales\Model\Order\Command\Handler\RemoveItemDiscount;
use Ecommerce\Sales\Model\Order\Command\Handler\RemoveOrder;
use Ecommerce\Sales\Model\Order\Command\Handler\RemoveOrderItem;
use Ecommerce\Sales\Model\Order\Command\Handler\SetAddress;
use Ecommerce\Sales\Model\Order\Command\Handler\SetCustomer;
use Ecommerce\Sales\Model\Order\Command\Handler\UpdateItemDiscount;
use Ecommerce\Sales\Model\Order\Command\Handler\UpdateOrder;
use Ecommerce\Sales\Model\Order\Command\Handler\UpdateOrderItem;
use Ecommerce\Sales\Model\Order\Event\OrderEvents;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Throwable;
use Ecommerce\Sales\Model\Order\Command\AddItemDiscount as CommandAddItemDiscount;
use Ecommerce\Sales\Model\Order\Command\AddOrderItem as CommandAddOrderItem;
use Ecommerce\Sales\Model\Order\Command\Order as CommandOrder;
use Ecommerce\Sales\Model\Order\Command\RemoveAddress as CommandRemoveAddress;
use Ecommerce\Sales\Model\Order\Command\RemoveItemDiscount as CommandRemoveItemDiscount;
use Ecommerce\Sales\Model\Order\Command\RemoveOrder as CommandRemoveOrder;
use Ecommerce\Sales\Model\Order\Command\RemoveOrderItem as CommandRemoveOrderItem;
use Ecommerce\Sales\Model\Order\Command\SetAddress as CommandSetAddress;
use Ecommerce\Sales\Model\Order\Command\SetCustomer as CommandSetCustomer;
use Ecommerce\Sales\Model\Order\Command\UpdateItemDiscount as CommandUpdateItemDiscount;
use Ecommerce\Sales\Model\Order\Command\UpdateOrder as CommandUpdateOrder;
use Ecommerce\Sales\Model\Order\Command\UpdateOrderItem as CommandUpdateOrderItem;

/**
 * Order Aggregate Facade.
 *
 * Hosts the inline command operations for this aggregate (writes).
 * Domain events are exposed via event() (constants on OrderEvents).
 * Query/list operations live on the sibling read facade.
 */
class Order extends EcommerceContext
{
    /**
     * Creates a new Order.
     *
     * @param CommandOrder $order
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function createOrder(CommandOrder $order, string $version = ''): DomainResponseInterface
    {
        return $this->context(CreateOrder::class, $order, $version)();
    }

    /**
     * Update Order operation.
     *
     * @param CommandUpdateOrder $updateOrder
     * @param string $version Optional ClassVersion override (default: caller version)
     * @param array<string, mixed> $expected Optional read basis (CAS): outer field name => value the caller read
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updateOrder(
        CommandUpdateOrder $updateOrder,
        string $version = '',
        array $expected = []
    ): DomainResponseInterface {
        return $this->context(UpdateOrder::class, $updateOrder, $version)($expected);
    }

    /**
     * Set Customer operation.
     *
     * @param CommandSetCustomer $setCustomer
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function setCustomer(CommandSetCustomer $setCustomer, string $version = ''): DomainResponseInterface
    {
        return $this->context(SetCustomer::class, $setCustomer, $version)();
    }

    /**
     * Set Address operation.
     *
     * @param CommandSetAddress $setAddress
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function setAddress(CommandSetAddress $setAddress, string $version = ''): DomainResponseInterface
    {
        return $this->context(SetAddress::class, $setAddress, $version)();
    }

    /**
     * Remove Address operation.
     *
     * @param CommandRemoveAddress $removeAddress
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeAddress(CommandRemoveAddress $removeAddress, string $version = ''): DomainResponseInterface
    {
        return $this->context(RemoveAddress::class, $removeAddress, $version)();
    }

    /**
     * Add OrderItem operation.
     *
     * @param CommandAddOrderItem $addOrderItem
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function addOrderItem(CommandAddOrderItem $addOrderItem, string $version = ''): DomainResponseInterface
    {
        return $this->context(AddOrderItem::class, $addOrderItem, $version)();
    }

    /**
     * Remove OrderItem operation.
     *
     * @param CommandRemoveOrderItem $removeOrderItem
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeOrderItem(
        CommandRemoveOrderItem $removeOrderItem,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(RemoveOrderItem::class, $removeOrderItem, $version)();
    }

    /**
     * Update OrderItem operation.
     *
     * @param CommandUpdateOrderItem $updateOrderItem
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updateOrderItem(
        CommandUpdateOrderItem $updateOrderItem,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(UpdateOrderItem::class, $updateOrderItem, $version)();
    }

    /**
     * Add ItemDiscount operation.
     *
     * @param CommandAddItemDiscount $addItemDiscount
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function addItemDiscount(
        CommandAddItemDiscount $addItemDiscount,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(AddItemDiscount::class, $addItemDiscount, $version)();
    }

    /**
     * Remove ItemDiscount operation.
     *
     * @param CommandRemoveItemDiscount $removeItemDiscount
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeItemDiscount(
        CommandRemoveItemDiscount $removeItemDiscount,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(RemoveItemDiscount::class, $removeItemDiscount, $version)();
    }

    /**
     * Update ItemDiscount operation.
     *
     * @param CommandUpdateItemDiscount $updateItemDiscount
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updateItemDiscount(
        CommandUpdateItemDiscount $updateItemDiscount,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(UpdateItemDiscount::class, $updateItemDiscount, $version)();
    }

    /**
     * Removes Order aggregate.
     *
     * @param CommandRemoveOrder $removeOrder
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeOrder(CommandRemoveOrder $removeOrder, string $version = ''): DomainResponseInterface
    {
        return $this->context(RemoveOrder::class, $removeOrder, $version)();
    }

    /**
     * Returns the Event registry.
     *
     * @return OrderEvents
     * @throws Throwable
     */
    public function event(): OrderEvents
    {
        return $this->handle(OrderEvents::class);
    }
}
