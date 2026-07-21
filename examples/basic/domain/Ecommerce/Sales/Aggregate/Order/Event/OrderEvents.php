<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Event;

/**
 * Order Event Registry.
 *
 * Provides access to all Order domain events.
 * Use constants for event subscriptions and getAll() for bulk registration.
 */
class OrderEvents
{
    public const ORDER_CREATED = OrderCreated::class;
    public const ORDER_REMOVED = OrderRemoved::class;
    public const ORDER_UPDATED = OrderUpdated::class;
    public const ORDER_CUSTOMER_UPDATED = OrderCustomerUpdated::class;
    public const ORDER_ADDRESS_UPDATED = OrderAddressUpdated::class;
    public const ORDER_ADDRESS_REMOVED = OrderAddressRemoved::class;
    public const ORDER_ORDER_ITEM_ADDED = OrderOrderItemAdded::class;
    public const ORDER_ORDER_ITEM_REMOVED = OrderOrderItemRemoved::class;
    public const ORDER_ITEM_DISCOUNT_ADDED = OrderItemDiscountAdded::class;
    public const ORDER_ITEM_DISCOUNT_REMOVED = OrderItemDiscountRemoved::class;

    public static function getAll(): array
    {
        return [
            'OrderCreated' => self::ORDER_CREATED,
            'OrderRemoved' => self::ORDER_REMOVED,
            'OrderUpdated' => self::ORDER_UPDATED,
            'OrderCustomerUpdated' => self::ORDER_CUSTOMER_UPDATED,
            'OrderAddressUpdated' => self::ORDER_ADDRESS_UPDATED,
            'OrderAddressRemoved' => self::ORDER_ADDRESS_REMOVED,
            'OrderOrderItemAdded' => self::ORDER_ORDER_ITEM_ADDED,
            'OrderOrderItemRemoved' => self::ORDER_ORDER_ITEM_REMOVED,
            'OrderItemDiscountAdded' => self::ORDER_ITEM_DISCOUNT_ADDED,
            'OrderItemDiscountRemoved' => self::ORDER_ITEM_DISCOUNT_REMOVED,
        ];
    }
}
