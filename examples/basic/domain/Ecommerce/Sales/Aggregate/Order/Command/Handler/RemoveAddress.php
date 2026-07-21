<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Order;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Aggregate\Order\Event\OrderAddressRemoved;
use Ecommerce\Sales\Aggregate\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\RemoveAddress as CommandRemoveAddress;
use Ecommerce\Sales\Aggregate\Order\Query\OrderByOrderNumber as QueryOrder;

/**
 * Command endpoint: RemoveAddress
 *
 * Operation: remove Address (one)
 */
class RemoveAddress extends EcommerceContext
{
    /**
     * Removes the Address entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandRemoveAddress $removeAddress */
            $removeAddress = $this->payload();

            $query = $this->handle(QueryOrder::class, orderNumber: $removeAddress->orderNumber);
            /** @var Order $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);

            $entityIdBeforeRemove = $handler->getData()->getCustomer()->getAddress()?->getId();

            $handler->removeAddress();

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['orderNumber' => $removeAddress->orderNumber]);

            $event = $this->handle(
                OrderAddressRemoved::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                addressId: $entityIdBeforeRemove,
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
