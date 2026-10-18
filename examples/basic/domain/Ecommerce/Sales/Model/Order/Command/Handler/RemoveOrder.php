<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Order;
use Ecommerce\Sales\Model\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\Order\Event\OrderRemoved;
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
use Ecommerce\Sales\Model\Order\Command\RemoveOrder as CommandRemoveOrder;
use Ecommerce\Sales\Model\Order\Query\OrderByOrderNumber as QueryOrder;

/**
 * Command endpoint: RemoveOrder
 */
class RemoveOrder extends EcommerceContext
{
    /**
     * Removes the Order aggregate.
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
            /** @var CommandRemoveOrder $removeOrder */
            $removeOrder = $this->payload();

            $query = $this->handle(QueryOrder::class, orderNumber: $removeOrder->orderNumber);
            /** @var Order|null $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderNumber' => $removeOrder->orderNumber]
                        )
                    )
                );
                return ($this->transformer())($this->result(), ResponseStatus::NotFound);
            }

            $handler->remove();

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'order',
                'orderNumber' => $removeOrder->orderNumber,
            ]);

            $event = $this->handle(
                OrderRemoved::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (ValidationException $e) {
            $this->result()->addError($e->getMessage());
            $this->result()->setData(
                $this->failureData(($this->handle(ValidationPayload::class))('validation', $e->fields))
            );
            return ($this->transformer())($this->result(), ResponseStatus::ValidationError);
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
            return ($this->transformer())($this->result(), ResponseStatus::Conflict);
        } catch (TransientConflictException $transient) {
            throw $transient;
        } catch (Throwable $e) {
            $this->result()->addError($e->getMessage());
            return ($this->transformer())($this->result(), ResponseStatus::InternalError);
        }
    }
}
