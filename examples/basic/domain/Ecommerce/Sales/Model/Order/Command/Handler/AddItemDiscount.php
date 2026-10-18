<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Order;
use Ecommerce\Sales\Model\Order\Command\Handler\Action\BuildAddItemDiscountData;
use Ecommerce\Sales\Model\Order\Command\Validation\ValidateAddItemDiscount;
use Ecommerce\Sales\Model\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\Order\Event\OrderItemDiscountAdded;
use Ecommerce\Sales\Model\Order\Repository\ConcurrencyConflictException;
use Ecommerce\Sales\Model\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Workflow\Handler\Failure\ConcurrencyConflictPayload;
use JardisSupport\Workflow\Handler\Failure\NotFoundPayload;
use JardisSupport\Workflow\Handler\Failure\ValidationPayload;
use Throwable;
use Ecommerce\Sales\Model\Order\Command\AddItemDiscount as CommandAddItemDiscount;
use Ecommerce\Sales\Model\Order\Query\OrderByOrderNumber as QueryOrder;

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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddItemDiscount $addItemDiscount */
            $addItemDiscount = $this->payload();

            $this->handle(ValidateAddItemDiscount::class)($addItemDiscount);

            $query = $this->handle(QueryOrder::class, orderNumber: $addItemDiscount->orderNumber);
            /** @var Order|null $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderNumber' => $addItemDiscount->orderNumber]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildAddItemDiscountData::class)($addItemDiscount);

            $miss = $handler->addItemDiscount($addItemDiscount->orderItemIdentifier, $entityData);
            if ($miss !== null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderItemIdentifier' => $addItemDiscount->orderItemIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $orderItem = $handler->getData()->getOrderItem();
            $itemDiscountIdentifier = null;
            foreach ($orderItem as $item) {
                if ($item->getIdentifier() === $addItemDiscount->orderItemIdentifier) {
                    $itemDiscountCollection = $item->getItemDiscount();
                    $itemDiscountIdentifier = !empty($itemDiscountCollection)
                        ? $itemDiscountCollection[array_key_last($itemDiscountCollection)]->getId()
                        : null;
                    break;
                }
            }
            $this->result()->setData([
                '@type' => 'order',
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
            $addedEntity = !empty($itemDiscountForEvent)
                ? $itemDiscountForEvent[array_key_last($itemDiscountForEvent)]
                : null;

            $event = $this->handle(
                OrderItemDiscountAdded::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                itemDiscountId: $addedEntity?->getId(),
                discountCode: $addedEntity?->getDiscountCode(),
                amount: $addedEntity?->getAmount(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (ValidationException $e) {
            $this->result()->addError($e->getMessage());
            $this->result()->setData(
                $this->failureData(($this->handle(ValidationPayload::class))('validation', $e->fields))
            );

            return ($this->transformer())(
                $this->result(),
                ResponseStatus::ValidationError
            );
        } catch (ConcurrencyConflictException $e) {
            $this->result()->setData(
                $this->failureData(
                    ($this->handle(ConcurrencyConflictPayload::class))(
                        'concurrencyConflict',
                        $e->getReason(),
                        $e->getAggregate(),
                        $e->getContext(),
                        $e->getViolated()
                    )
                )
            );

            return ($this->transformer())(
                $this->result(),
                ResponseStatus::Conflict
            );
        } catch (TransientConflictException $transient) {
            throw $transient;
        } catch (Throwable $e) {
            $this->result()->addError($e->getMessage());

            return ($this->transformer())(
                $this->result(),
                ResponseStatus::InternalError
            );
        }
    }
}
