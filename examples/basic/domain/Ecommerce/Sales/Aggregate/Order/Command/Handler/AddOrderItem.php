<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Order;
use Ecommerce\Sales\Aggregate\Order\Command\Handler\Action\BuildAddOrderItemData;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidateAddOrderItem;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Aggregate\Order\Event\OrderOrderItemAdded;
use Ecommerce\Sales\Aggregate\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\AddOrderItem as CommandAddOrderItem;
use Ecommerce\Sales\Aggregate\Order\Query\OrderByOrderNumber as QueryOrder;

/**
 * Command endpoint: AddOrderItem
 *
 * Operation: add OrderItem (many)
 */
class AddOrderItem extends EcommerceContext
{
    /**
     * Adds the OrderItem entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddOrderItem $addOrderItem */
            $addOrderItem = $this->payload();

            $query = $this->handle(QueryOrder::class, orderNumber: $addOrderItem->orderNumber);
            /** @var Order $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);

            $this->handle(ValidateAddOrderItem::class)($addOrderItem);
            $entityData = $this->handle(BuildAddOrderItemData::class)($addOrderItem);

            $handler->addOrderItem($entityData);

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $orderItem = $handler->getData()->getOrderItem();
            $this->result()->setData([
                'orderNumber' => $addOrderItem->orderNumber,
                'orderItemIdentifier' => $orderItem[array_key_last($orderItem)]->getIdentifier(),
            ]);

            $orderItemForEvent = $handler->getData()->getOrderItem();
            $addedEntity = !empty($orderItemForEvent) ? $orderItemForEvent[array_key_last($orderItemForEvent)] : null;

            $event = $this->handle(
                OrderOrderItemAdded::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                orderItemIdentifier: $addedEntity?->getIdentifier(),
                productIdentifier: $addedEntity?->getProductIdentifier(),
                quantity: $addedEntity?->getQuantity(),
                unitPrice: $addedEntity?->getUnitPrice(),
                subtotal: $addedEntity?->getSubtotal(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return $this->handle(DomainResponseTransformer::class)->transform($this->result());
        } catch (ValidationException $e) {
            $this->result()->addError($e->getMessage());

            return $this->handle(DomainResponseTransformer::class)->transform(
                $this->result(),
                ResponseStatus::ValidationError
            );
        } catch (\Throwable $e) {
            $this->result()->addError($e->getMessage());

            return $this->handle(DomainResponseTransformer::class)->transform(
                $this->result(),
                ResponseStatus::InternalError
            );
        }
    }
}
