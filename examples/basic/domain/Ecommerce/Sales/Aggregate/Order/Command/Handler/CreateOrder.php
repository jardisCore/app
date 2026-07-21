<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Order;
use Ecommerce\Sales\Aggregate\Order\Command\Handler\Action\HydrateCreateOrderEntities;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidateCreateOrder;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Aggregate\Order\Event\OrderCreated;
use Ecommerce\Sales\Aggregate\Order\Repository\OrderRepository;
use Ecommerce\Sales\Rule\Data\RuleResult;
use Ecommerce\Sales\Rule\Guard\GuardOrder;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\Order as CommandOrder;

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
     * @throws Exception
     * @throws Throwable
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

            $this->handle(HydrateCreateOrderEntities::class)($handler, $order);

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['orderNumber' => $handler->getData()->getOrderNumber()]);

            $event = $this->handle(OrderCreated::class, orderNumber: $handler->getData()->getOrderNumber(), occurredAt: new DateTimeImmutable());
            $this->result()->addEvent($event, EventScope::Internal);

            return $this->handle(DomainResponseTransformer::class)->transform($this->result(), ResponseStatus::Created);
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
