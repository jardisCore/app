<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Order;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Aggregate\Order\Event\OrderOrderItemRemoved;
use Ecommerce\Sales\Aggregate\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use RuntimeException;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\RemoveOrderItem as CommandRemoveOrderItem;
use Ecommerce\Sales\Aggregate\Order\Query\OrderByOrderNumber as QueryOrder;

/**
 * Command endpoint: RemoveOrderItem
 *
 * Operation: remove OrderItem (many)
 */
class RemoveOrderItem extends EcommerceContext
{
    /**
     * Removes an OrderItem from the collection.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandRemoveOrderItem $removeOrderItem */
            $removeOrderItem = $this->payload();

            $query = $this->handle(QueryOrder::class, orderNumber: $removeOrderItem->orderNumber);
            /** @var Order $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);

            $items = $handler->getData()->getOrderItem();
            $found = false;
            foreach ($items as $item) {
                if ($item->getIdentifier() === $removeOrderItem->orderItemIdentifier) {
                    $handler->removeOrderItem($item->getId());
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new RuntimeException("OrderItem not found: " . $removeOrderItem->orderItemIdentifier);
            }

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['orderNumber' => $removeOrderItem->orderNumber]);

            $event = $this->handle(
                OrderOrderItemRemoved::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                orderItemIdentifier: $removeOrderItem->orderItemIdentifier,
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
