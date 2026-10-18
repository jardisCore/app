<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Order;
use Ecommerce\Sales\Model\Order\Command\Handler\Action\BuildAddOrderItemData;
use Ecommerce\Sales\Model\Order\Command\Validation\ValidateAddOrderItem;
use Ecommerce\Sales\Model\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\Order\Event\OrderOrderItemAdded;
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
use Ecommerce\Sales\Model\Order\Command\AddOrderItem as CommandAddOrderItem;
use Ecommerce\Sales\Model\Order\Query\OrderByOrderNumber as QueryOrder;

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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddOrderItem $addOrderItem */
            $addOrderItem = $this->payload();

            $this->handle(ValidateAddOrderItem::class)($addOrderItem);

            $query = $this->handle(QueryOrder::class, orderNumber: $addOrderItem->orderNumber);
            /** @var Order|null $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderNumber' => $addOrderItem->orderNumber]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildAddOrderItemData::class)($addOrderItem);

            $handler->addOrderItem($entityData);

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $orderItem = $handler->getData()->getOrderItem();
            $this->result()->setData([
                '@type' => 'order',
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
