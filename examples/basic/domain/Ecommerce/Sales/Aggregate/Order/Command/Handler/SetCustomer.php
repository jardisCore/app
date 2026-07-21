<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Order;
use Ecommerce\Sales\Aggregate\Order\Command\Handler\Action\BuildSetCustomerData;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidateSetCustomer;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Aggregate\Order\Event\OrderCustomerUpdated;
use Ecommerce\Sales\Aggregate\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\SetCustomer as CommandSetCustomer;
use Ecommerce\Sales\Aggregate\Order\Query\OrderByOrderNumber as QueryOrder;

/**
 * Command endpoint: SetCustomer
 *
 * Operation: set Customer (one)
 */
class SetCustomer extends EcommerceContext
{
    /**
     * Sets/replaces the Customer entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandSetCustomer $setCustomer */
            $setCustomer = $this->payload();

            $query = $this->handle(QueryOrder::class, orderNumber: $setCustomer->orderNumber);
            /** @var Order $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);

            $this->handle(ValidateSetCustomer::class)($setCustomer);
            $entityData = $this->handle(BuildSetCustomerData::class)($setCustomer);

            $handler->setCustomer($entityData);
            $handler->setAddress($entityData['address']);

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                'orderNumber' => $setCustomer->orderNumber,
                'customerIdentifier' => $handler->getData()->getCustomer()?->getIdentifier(),
            ]);

            $event = $this->handle(
                OrderCustomerUpdated::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                email: $handler->getData()->getCustomer()?->getEmail(),
                customerName: $handler->getData()->getCustomer()?->getName(),
                phone: $handler->getData()->getCustomer()?->getPhone(),
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
