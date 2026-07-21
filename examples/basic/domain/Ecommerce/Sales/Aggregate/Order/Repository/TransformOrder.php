<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Repository;

use Ecommerce\EcommerceContext;

/**
 * Transforms flat query results into nested Order structure.
 *
 * Input: Arrays grouped by entity name (from query results)
 * Output: Nested array structure matching aggregate definition
 */
class TransformOrder extends EcommerceContext
{
    /**
     * Transform flat query results into nested aggregate structure.
     *
     * @param array<string, array<int, mixed>> $container Query results grouped by entity
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(array $container = []): array
    {
        $result = [];
        $dto = [];
        $processedIds = [];

        foreach ($container['order'] as $index => $order) {
            if (is_numeric($index) && !isset($processedIds[$order['id']])) {
                $processedIds[$order['id']] = true;
                $parent = $dto['order'] = $order;

                $this->transformOrderCustomer($parent, $dto, $container);
                $this->transformOrderOrderItem($parent, $dto, $container);

                $result[] = $parent;
            }
        }

        return $result;
    }

    /**
     * Transform customer entities (ERM: one).
     * Relates: id = order.customer_id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     */
    protected function transformOrderCustomer(array &$parent, array $dto, array &$container): void
    {
        foreach ($container['customer'] as $index => $customer) {
            if (is_numeric($index) && $customer['id'] == $dto['order']['customer_id']) {
                $parent['customer'] = $dto['customer'] = $customer;

                $this->transformCustomerAddress($parent['customer'], $dto, $container);

                break; // ERM: one - stop after first match
            }
        }
    }
    /**
     * Transform address entities (ERM: one).
     * Relates: id = customer.billing_address_id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     */
    protected function transformCustomerAddress(array &$parent, array $dto, array &$container): void
    {
        foreach ($container['address'] as $index => $address) {
            if (is_numeric($index) && $address['id'] == $dto['customer']['billing_address_id']) {
                $parent['address'] = $address;

                break; // ERM: one - stop after first match
            }
        }
    }
    /**
     * Transform orderItem entities (ERM: many).
     * Relates: order_id = order.id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     */
    protected function transformOrderOrderItem(array &$parent, array $dto, array &$container): void
    {
        foreach ($container['orderItem'] as $index => $orderItem) {
            if (is_numeric($index) && $orderItem['order_id'] == $dto['order']['id']) {
                $dto['orderItem'] = $orderItem;

                $this->transformOrderItemItemDiscount($orderItem, $dto, $container);

                $parent['orderItem'][] = $orderItem;
            }
        }
    }
    /**
     * Transform itemDiscount entities (ERM: many).
     * Relates: item_id = orderItem.id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     */
    protected function transformOrderItemItemDiscount(array &$parent, array $dto, array &$container): void
    {
        foreach ($container['itemDiscount'] as $index => $itemDiscount) {
            if (is_numeric($index) && $itemDiscount['item_id'] == $dto['orderItem']['id']) {
                $parent['itemDiscount'][] = $itemDiscount;
            }
        }
    }
}
