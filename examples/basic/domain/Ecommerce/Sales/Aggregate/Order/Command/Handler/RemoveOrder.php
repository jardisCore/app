<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Order;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Aggregate\Order\Event\OrderRemoved;
use Ecommerce\Sales\Aggregate\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\RemoveOrder as CommandRemoveOrder;
use Ecommerce\Sales\Aggregate\Order\Query\OrderByOrderNumber as QueryOrder;

/**
 * Command endpoint: RemoveOrder
 */
class RemoveOrder extends EcommerceContext
{
    /**
     * Removes the Order aggregate.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandRemoveOrder $removeOrder */
            $removeOrder = $this->payload();

            $query = $this->handle(QueryOrder::class, orderNumber: $removeOrder->orderNumber);
            /** @var Order $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);

            $handler->remove();

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['orderNumber' => $removeOrder->orderNumber]);

            $event = $this->handle(OrderRemoved::class, orderNumber: $handler->getData()->getOrderNumber(), occurredAt: new DateTimeImmutable());
            $this->result()->addEvent($event, EventScope::Internal);

            return $this->handle(DomainResponseTransformer::class)->transform($this->result());
        } catch (ValidationException $e) {
            $this->result()->addError($e->getMessage());
            return $this->handle(DomainResponseTransformer::class)->transform($this->result(), ResponseStatus::ValidationError);
        } catch (\Throwable $e) {
            $this->result()->addError($e->getMessage());
            return $this->handle(DomainResponseTransformer::class)->transform($this->result(), ResponseStatus::InternalError);
        }
    }
}
