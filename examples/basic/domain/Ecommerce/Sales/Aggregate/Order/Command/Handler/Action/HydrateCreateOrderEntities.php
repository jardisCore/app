<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Order;
use Ecommerce\Sales\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\Order as CommandOrder;

/**
 * Action: HydrateCreateOrderEntities
 */
class HydrateCreateOrderEntities extends EcommerceContext
{
    /**
     * Hydrates all entities from the command DTO.
     *
     * @param Order $handler Aggregate handler
     * @param CommandOrder $order Command data
     * @throws Throwable
     */
    public function __invoke(Order $handler, CommandOrder $order): void
    {
        $this->hydrateOrder($handler, $order);
        $this->hydrateCustomer($handler, $order);
        $this->hydrateAddress($handler, $order);
        $this->hydrateOrderItem($handler, $order);
    }

    /**
     * Hydrates Order entity data.
     * @throws Throwable
     */
    protected function hydrateOrder(Order $handler, CommandOrder $order): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($order);

        $handler->setOrder($this->handle(FieldMapper::class)->toColumns(array_filter(
            $rawData,
            fn($key) => !in_array($key, ["customer","orderItem"], true),
            ARRAY_FILTER_USE_KEY
        ), $fieldMap->ordersColumns()));
    }

    /**
     * Hydrates Customer entity data.
     * @throws Throwable
     */
    protected function hydrateCustomer(Order $handler, CommandOrder $order): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($order->customer);

        $handler->setCustomer($this->handle(FieldMapper::class)->toColumns(array_filter(
            $rawData,
            fn($key) => !in_array($key, ["address"], true),
            ARRAY_FILTER_USE_KEY
        ), $fieldMap->customersColumns()));
    }

    /**
     * Hydrates Address entity data.
     * @throws Throwable
     */
    protected function hydrateAddress(Order $handler, CommandOrder $order): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        $handler->setAddress($this->handle(FieldMapper::class)->toColumns(get_object_vars($order->customer->address), $fieldMap->addressesColumns()));
    }

    /**
     * Hydrates OrderItem collection.
     * @throws Throwable
     */
    protected function hydrateOrderItem(Order $handler, CommandOrder $order): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        // OrderItem collection
        foreach ($order->orderItem as $orderItemDto) {
            $itemDiscountDataList = [];
            foreach ($orderItemDto->itemDiscount as $itemDiscountDto) {
                $itemDiscountDataList[] = $this->handle(FieldMapper::class)->toColumns(get_object_vars($itemDiscountDto), $fieldMap->itemDiscountsColumns());
            }
            $entityData = $this->handle(FieldMapper::class)->toColumns([
                ...array_filter(
                    get_object_vars($orderItemDto),
                    fn($key) => !in_array($key, ["itemDiscount"], true),
                    ARRAY_FILTER_USE_KEY
                ),
            ], $fieldMap->orderItemsColumns());
            if (!empty($itemDiscountDataList)) {
                $entityData['itemDiscount'] = $itemDiscountDataList;
            }
            $handler->addOrderItem($entityData);
        }
    }
}
