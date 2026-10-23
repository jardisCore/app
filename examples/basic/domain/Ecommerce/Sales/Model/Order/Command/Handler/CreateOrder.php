<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Guard\GuardOrder;
use Ecommerce\Sales\Model\Order\Aggregate\Order;
use Ecommerce\Sales\Model\Order\Command\Handler\Action\HydrateCreateOrderEntities;
use Ecommerce\Sales\Model\Order\Command\Validation\CreateOrderValidatorRegistry;
use Ecommerce\Sales\Model\Order\Event\OrderCreated;
use Ecommerce\Sales\Model\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Response\Data\RuleResult;
use JardisSupport\Response\Handler\Door\ValidateInput;
use JardisSupport\Response\Handler\Failure\LogCause;
use JardisSupport\Response\Handler\Failure\MapThrowable;
use JardisSupport\Response\Handler\Failure\RuleViolationPayload;
use JardisSupport\Response\Handler\Failure\ValidationPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use JardisSupport\Validation\ObjectValidator;
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
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandOrder $order */
        $order = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(CreateOrderValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($order);
        if ($findings->fields !== []) {
            foreach ($findings->errors as $error) {
                $this->result()->addError($error);
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))(
                    ResponseStatus::ValidationError,
                    ($this->handle(ValidationPayload::class))('validation', $findings->fields),
                    null,
                    ''
                )
            );

            return ($this->transformer())($this->result(), ResponseStatus::ValidationError);
        }
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $owner = $connection?->inTransaction() !== true;

        try {
            /** @var Order $handler */
            $handler = $this->handle(OrderRepository::class)->createNew();

            /** @var RuleResult $guardResult */
            $guardResult = ($this->handle(GuardOrder::class))($order);
            if (!$guardResult->passed) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::RuleViolation,
                        ($this->handle(RuleViolationPayload::class))(
                            $guardResult->rule,
                            $guardResult->messageKey,
                            $guardResult->context
                        ),
                        null,
                        ''
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
                'id' => $handler->getData()->getId(),
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
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Order');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'CreateOrder');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
