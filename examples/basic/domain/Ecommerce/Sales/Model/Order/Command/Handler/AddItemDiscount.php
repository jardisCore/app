<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Order;
use Ecommerce\Sales\Model\Order\Command\Handler\Action\BuildAddItemDiscountData;
use Ecommerce\Sales\Model\Order\Command\Validation\AddItemDiscountValidatorRegistry;
use Ecommerce\Sales\Model\Order\Event\OrderItemDiscountAdded;
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
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandAddItemDiscount $addItemDiscount */
        $addItemDiscount = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(AddItemDiscountValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($addItemDiscount);
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
            $query = $this->handle(
                QueryOrder::class,
                tenant: $addItemDiscount->tenant,
                orderNumber: $addItemDiscount->orderNumber
            );
            /** @var Order|null $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderNumber' => $addItemDiscount->orderNumber]
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

            $entityData = $this->handle(BuildAddItemDiscountData::class)($addItemDiscount);

            $miss = $handler->addItemDiscount($addItemDiscount->orderItemIdentifier, $entityData);
            if ($miss !== null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderItemIdentifier' => $addItemDiscount->orderItemIdentifier]
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
                'id' => $handler->getData()->getId(),
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
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Order');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'AddItemDiscount');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
