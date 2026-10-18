<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Order;
use Ecommerce\Sales\Model\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\Order\Event\OrderItemDiscountRemoved;
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
use Ecommerce\Sales\Model\Order\Command\RemoveItemDiscount as CommandRemoveItemDiscount;
use Ecommerce\Sales\Model\Order\Query\OrderByOrderNumber as QueryOrder;

/**
 * Command endpoint: RemoveItemDiscount
 *
 * Operation: remove ItemDiscount (many)
 */
class RemoveItemDiscount extends EcommerceContext
{
    /**
     * Removes an ItemDiscount from the collection.
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
            /** @var CommandRemoveItemDiscount $removeItemDiscount */
            $removeItemDiscount = $this->payload();

            $query = $this->handle(QueryOrder::class, orderNumber: $removeItemDiscount->orderNumber);
            /** @var Order|null $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderNumber' => $removeItemDiscount->orderNumber]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $parent = null;
            foreach ($handler->getData()->getOrderItem() as $candidate) {
                if ($candidate->getIdentifier() === $removeItemDiscount->orderItemIdentifier) {
                    $parent = $candidate;
                    break;
                }
            }
            if ($parent === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderItemIdentifier' => $removeItemDiscount->orderItemIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }
            $items = $parent->getItemDiscount();
            $found = false;
            foreach ($items as $item) {
                if ($item->getId() === $removeItemDiscount->itemDiscountId) {
                    $handler->removeItemDiscount($item->getId());
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['itemDiscountId' => $removeItemDiscount->itemDiscountId]
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

            $this->result()->setData([
                '@type' => 'order',
                'orderNumber' => $removeItemDiscount->orderNumber,
            ]);

            $event = $this->handle(
                OrderItemDiscountRemoved::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                itemDiscountId: $removeItemDiscount->itemDiscountId,
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
