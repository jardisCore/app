<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Data\RuleResult;
use Ecommerce\Sales\Closure\Guard\GuardOrder;
use Ecommerce\Sales\Model\Order\Aggregate\Order;
use Ecommerce\Sales\Model\Order\Command\Handler\Action\HydrateCreateOrderEntities;
use Ecommerce\Sales\Model\Order\Command\Validation\ValidateCreateOrder;
use Ecommerce\Sales\Model\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\Order\Event\OrderCreated;
use Ecommerce\Sales\Model\Order\Repository\ConcurrencyConflictException;
use Ecommerce\Sales\Model\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Workflow\Handler\Failure\ConcurrencyConflictPayload;
use JardisSupport\Workflow\Handler\Failure\RuleViolationPayload;
use JardisSupport\Workflow\Handler\Failure\ValidationPayload;
use Throwable;
use Ecommerce\Sales\Model\Order\Command\Order as CommandOrder;

/**
 * Command endpoint: CreateOrder
 *
 * Creates a new Order aggregate
 */
class CreateOrder extends EcommerceContext
{
    /**
     * Creates a new Order.
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
            /** @var CommandOrder $order */
            $order = $this->payload();

            /** @var Order $handler */
            $handler = $this->handle(OrderRepository::class)->createNew();
            $this->handle(ValidateCreateOrder::class)($order);

            /** @var RuleResult $guardResult */
            $guardResult = ($this->handle(GuardOrder::class))($order);
            if (!$guardResult->passed) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(RuleViolationPayload::class))(
                            $guardResult->rule,
                            $guardResult->messageKey,
                            $guardResult->context
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::RuleViolation
                );
            }

            $this->handle(HydrateCreateOrderEntities::class)($handler, $order);

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'order',
                'orderNumber' => $handler->getData()->getOrderNumber(),
                'customerIdentifier' => $handler->getData()->getCustomer()?->getIdentifier(),
                'orderItemIdentifiers' => array_map(fn($e) => $e->getIdentifier(), $handler->getData()->getOrderItem()),
            ]);

            $event = $this->handle(
                OrderCreated::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result(), ResponseStatus::Created);
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
