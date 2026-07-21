<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Order;
use Ecommerce\Sales\Aggregate\Order\Command\Handler\Action\BuildAddItemDiscountData;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidateAddItemDiscount;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Aggregate\Order\Event\OrderItemDiscountAdded;
use Ecommerce\Sales\Aggregate\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\AddItemDiscount as CommandAddItemDiscount;
use Ecommerce\Sales\Aggregate\Order\Query\OrderByOrderNumber as QueryOrder;

/**
 * Command endpoint: AddItemDiscount
 *
 * Operation: add ItemDiscount (many)
 */
class AddItemDiscount extends EcommerceContext
{
    /**
     * Adds the ItemDiscount entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddItemDiscount $addItemDiscount */
            $addItemDiscount = $this->payload();

            $query = $this->handle(QueryOrder::class, orderNumber: $addItemDiscount->orderNumber);
            /** @var Order $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);

            $this->handle(ValidateAddItemDiscount::class)($addItemDiscount);
            $entityData = $this->handle(BuildAddItemDiscountData::class)($addItemDiscount);

            $handler->addItemDiscount($addItemDiscount->orderItemIdentifier, $entityData);

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $orderItem = $handler->getData()->getOrderItem();
            $itemDiscountIdentifier = null;
            foreach ($orderItem as $item) {
                if ($item->getIdentifier() === $addItemDiscount->orderItemIdentifier) {
                    $itemDiscountCollection = $item->getItemDiscount();
                    $itemDiscountIdentifier = !empty($itemDiscountCollection) ? $itemDiscountCollection[array_key_last($itemDiscountCollection)]->getId() : null;
                    break;
                }
            }
            $this->result()->setData([
                'orderNumber' => $addItemDiscount->orderNumber,
                'orderItemIdentifier' => $addItemDiscount->orderItemIdentifier,
                'itemDiscountId' => $itemDiscountIdentifier,
            ]);

            $itemDiscountForEvent = [];
            foreach ($handler->getData()->getOrderItem() as $parentItem) {
                if ($parentItem->getIdentifier() === $addItemDiscount->orderItemIdentifier) {
                    $itemDiscountForEvent = $parentItem->getItemDiscount();
                    break;
                }
            }
            $addedEntity = !empty($itemDiscountForEvent) ? $itemDiscountForEvent[array_key_last($itemDiscountForEvent)] : null;

            $event = $this->handle(
                OrderItemDiscountAdded::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                itemDiscountId: $addedEntity?->getId(),
                discountCode: $addedEntity?->getDiscountCode(),
                amount: $addedEntity?->getAmount(),
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
