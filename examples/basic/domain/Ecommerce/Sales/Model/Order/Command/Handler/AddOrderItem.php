<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Order;
use Ecommerce\Sales\Model\Order\Command\Handler\Action\BuildAddOrderItemData;
use Ecommerce\Sales\Model\Order\Command\Validation\AddOrderItemValidatorRegistry;
use Ecommerce\Sales\Model\Order\Event\OrderOrderItemAdded;
use Ecommerce\Sales\Model\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Response\Handler\Door\ValidateInput;
use JardisSupport\Response\Handler\Failure\LogCause;
use JardisSupport\Response\Handler\Failure\MapThrowable;
use JardisSupport\Response\Handler\Failure\NotFoundPayload;
use JardisSupport\Response\Handler\Failure\ValidationPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use JardisSupport\Validation\ObjectValidator;
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
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandAddOrderItem $addOrderItem */
        $addOrderItem = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(AddOrderItemValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($addOrderItem);
        if ($findings->fields !== []) {
            foreach ($findings->errors as $error) {
                $this->result()->addError($error);
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))(
                    ResponseStatus::ValidationError,
                    ($this->handle(ValidationPayload::class))('validation', $findings->fields),
                    null,
                    ''
                )
            );

            return ($this->transformer())($this->result(), ResponseStatus::ValidationError);
        }
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $owner = $connection?->inTransaction() !== true;

        try {
            $query = $this->handle(QueryOrder::class, orderNumber: $addOrderItem->orderNumber);
            /** @var Order|null $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderNumber' => $addOrderItem->orderNumber]
                        ),
                        null,
                        ''
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
                'id' => $handler->getData()->getId(),
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
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Order');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'AddOrderItem');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
