<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Guard\GuardUpdateOrderItem;
use Ecommerce\Sales\Model\Order\Aggregate\Order;
use Ecommerce\Sales\Model\Order\Command\Handler\Action\BuildUpdateOrderItemData;
use Ecommerce\Sales\Model\Order\Command\Validation\UpdateOrderItemValidatorRegistry;
use Ecommerce\Sales\Model\Order\Event\OrderOrderItemUpdated;
use Ecommerce\Sales\Model\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Response\Data\RuleResult;
use JardisSupport\Response\Handler\Door\ValidateInput;
use JardisSupport\Response\Handler\Failure\LogCause;
use JardisSupport\Response\Handler\Failure\MapThrowable;
use JardisSupport\Response\Handler\Failure\NotFoundPayload;
use JardisSupport\Response\Handler\Failure\RuleViolationPayload;
use JardisSupport\Response\Handler\Failure\ValidationPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use JardisSupport\Validation\ObjectValidator;
use Throwable;
use Ecommerce\Sales\Model\Order\Command\UpdateOrderItem as CommandUpdateOrderItem;
use Ecommerce\Sales\Model\Order\Query\OrderByOrderNumber as QueryOrder;

/**
 * Command endpoint: UpdateOrderItem
 *
 * Operation: update OrderItem (many)
 */
class UpdateOrderItem extends EcommerceContext
{
    /**
     * Updates an OrderItem in the collection.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandUpdateOrderItem $updateOrderItem */
        $updateOrderItem = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(UpdateOrderItemValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($updateOrderItem);
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
            $query = $this->handle(QueryOrder::class, orderNumber: $updateOrderItem->orderNumber);
            /** @var Order|null $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderNumber' => $updateOrderItem->orderNumber]
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

            /** @var RuleResult $guardResult */
            $guardResult = ($this->handle(GuardUpdateOrderItem::class))($updateOrderItem, $handler->getData());
            if (!$guardResult->passed) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::RuleViolation,
                        ($this->handle(RuleViolationPayload::class))(
                            $guardResult->rule,
                            $guardResult->messageKey,
                            $guardResult->context
                        ),
                        null,
                        ''
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::RuleViolation
                );
            }

            $entityData = $this->handle(BuildUpdateOrderItemData::class)($updateOrderItem);

            $items = $handler->getData()->getOrderItem();
            $found = false;
            foreach ($items as $item) {
                if ($item->getIdentifier() === $updateOrderItem->orderItemIdentifier) {
                    $handler->updateOrderItem($item->getId(), $entityData);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderItemIdentifier' => $updateOrderItem->orderItemIdentifier]
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

            $this->result()->setData([
                '@type' => 'order',
                'id' => $handler->getData()->getId(),
                'orderNumber' => $updateOrderItem->orderNumber,
                'orderItemIdentifier' => $updateOrderItem->orderItemIdentifier,
            ]);

            $event = $this->handle(
                OrderOrderItemUpdated::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                orderItemIdentifier: $updateOrderItem->orderItemIdentifier,
                productIdentifier: $updateOrderItem->productIdentifier,
                quantity: $updateOrderItem->quantity,
                unitPrice: $updateOrderItem->unitPrice,
                subtotal: $updateOrderItem->subtotal,
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Order');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'UpdateOrderItem');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
