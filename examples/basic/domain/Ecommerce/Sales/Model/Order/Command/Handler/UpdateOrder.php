<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Data\RuleResult;
use Ecommerce\Sales\Closure\Guard\GuardUpdateOrder;
use Ecommerce\Sales\Model\Order\Aggregate\Order;
use Ecommerce\Sales\Model\Order\Command\Handler\Action\BuildUpdateOrderData;
use Ecommerce\Sales\Model\Order\Command\Handler\Action\BuildUpdateOrderExpected;
use Ecommerce\Sales\Model\Order\Command\Validation\ValidateUpdateOrder;
use Ecommerce\Sales\Model\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Model\Order\Event\OrderUpdated;
use Ecommerce\Sales\Model\Order\Repository\ConcurrencyConflictException;
use Ecommerce\Sales\Model\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Workflow\Handler\Failure\ConcurrencyConflictPayload;
use JardisSupport\Workflow\Handler\Failure\NotFoundPayload;
use JardisSupport\Workflow\Handler\Failure\RuleViolationPayload;
use JardisSupport\Workflow\Handler\Failure\ValidationPayload;
use RuntimeException;
use Throwable;
use Ecommerce\Sales\Model\Order\Command\UpdateOrder as CommandUpdateOrder;
use Ecommerce\Sales\Model\Order\Query\OrderByOrderNumber as QueryOrder;

/**
 * Command endpoint: UpdateOrder
 *
 * Operation: update Order (root)
 */
class UpdateOrder extends EcommerceContext
{
    /**
     * Updates the root Order entity.
     *
     * @param array<string, mixed> $expected Optional read basis (CAS): outer field name => value the caller read
     * @return DomainResponseInterface
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(array $expected = []): DomainResponseInterface
    {
        try {
            /** @var CommandUpdateOrder $updateOrder */
            $updateOrder = $this->payload();

            $this->handle(ValidateUpdateOrder::class)($updateOrder);

            $query = $this->handle(QueryOrder::class, orderNumber: $updateOrder->orderNumber);
            /** @var Order|null $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderNumber' => $updateOrder->orderNumber]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            /** @var RuleResult $guardResult */
            $guardResult = ($this->handle(GuardUpdateOrder::class))($updateOrder, $handler->getData());
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

            $entityData = $this->handle(BuildUpdateOrderData::class)($updateOrder);

            if ($expected !== []) {
                $handler->overlayReadBasis($this->handle(BuildUpdateOrderExpected::class)($expected));
            }

            $handler->setOrder($entityData);

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'order',
                'orderNumber' => $updateOrder->orderNumber,
            ]);

            $event = $this->handle(
                OrderUpdated::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                totalAmount: $handler->getData()->getTotalAmount(),
                status: $handler->getData()->getStatus()?->value ?? throw new RuntimeException('status is required'),
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
