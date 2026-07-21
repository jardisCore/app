<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Order;
use Ecommerce\Sales\Aggregate\Order\Command\Handler\Action\BuildSetAddressData;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidateSetAddress;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Aggregate\Order\Event\OrderAddressUpdated;
use Ecommerce\Sales\Aggregate\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\SetAddress as CommandSetAddress;
use Ecommerce\Sales\Aggregate\Order\Query\OrderByOrderNumber as QueryOrder;

/**
 * Command endpoint: SetAddress
 *
 * Operation: set Address (one)
 */
class SetAddress extends EcommerceContext
{
    /**
     * Sets/replaces the Address entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandSetAddress $setAddress */
            $setAddress = $this->payload();

            $query = $this->handle(QueryOrder::class, orderNumber: $setAddress->orderNumber);
            /** @var Order $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);

            $this->handle(ValidateSetAddress::class)($setAddress);
            $entityData = $this->handle(BuildSetAddressData::class)($setAddress);

            $handler->setAddress($entityData);

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                'orderNumber' => $setAddress->orderNumber,
                'addressId' => $handler->getData()->getCustomer()->getAddress()?->getId(),
            ]);

            $event = $this->handle(
                OrderAddressUpdated::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                street: $handler->getData()->getCustomer()->getAddress()?->getStreet(),
                city: $handler->getData()->getCustomer()->getAddress()?->getCity(),
                postalCode: $handler->getData()->getCustomer()->getAddress()?->getPostalCode(),
                country: $handler->getData()->getCustomer()->getAddress()?->getCountry(),
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
