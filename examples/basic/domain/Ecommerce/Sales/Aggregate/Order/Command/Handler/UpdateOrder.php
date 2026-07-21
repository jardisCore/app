<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Order;
use Ecommerce\Sales\Aggregate\Order\Command\Handler\Action\BuildUpdateOrderData;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidateUpdateOrder;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Aggregate\Order\Event\OrderUpdated;
use Ecommerce\Sales\Aggregate\Order\Repository\OrderRepository;
use Ecommerce\Sales\Rule\Data\RuleResult;
use Ecommerce\Sales\Rule\Guard\GuardUpdateOrder;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use RuntimeException;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\UpdateOrder as CommandUpdateOrder;
use Ecommerce\Sales\Aggregate\Order\Query\OrderByOrderNumber as QueryOrder;

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
     * @return DomainResponseInterface
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandUpdateOrder $updateOrder */
            $updateOrder = $this->payload();

            $query = $this->handle(QueryOrder::class, orderNumber: $updateOrder->orderNumber);
            /** @var Order $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);

            $this->handle(ValidateUpdateOrder::class)($updateOrder);

            /** @var RuleResult $guardResult */
            $guardResult = ($this->handle(GuardUpdateOrder::class))($updateOrder);
            if (!$guardResult->passed) {
                $this->result()->setData([
                    'rule' => $guardResult->rule,
                    'messageKey' => $guardResult->messageKey,
                    'context' => $guardResult->context,
                ]);

                return $this->handle(DomainResponseTransformer::class)->transform(
                    $this->result(),
                    ResponseStatus::RuleViolation
                );
            }

            $entityData = $this->handle(BuildUpdateOrderData::class)($updateOrder);

            $handler->setOrder($entityData);

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['orderNumber' => $updateOrder->orderNumber]);

            $event = $this->handle(
                OrderUpdated::class,
                orderNumber: $handler->getData()->getOrderNumber(),
                totalAmount: $handler->getData()->getTotalAmount(),
                status: $handler->getData()->getStatus()?->value ?? throw new RuntimeException('status is required'),
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
