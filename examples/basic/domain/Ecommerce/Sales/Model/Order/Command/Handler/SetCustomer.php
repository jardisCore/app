<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Order;
use Ecommerce\Sales\Model\Order\Command\Handler\Action\BuildSetCustomerData;
use Ecommerce\Sales\Model\Order\Command\Validation\SetCustomerValidatorRegistry;
use Ecommerce\Sales\Model\Order\Event\OrderCustomerUpdated;
use Ecommerce\Sales\Model\Order\Repository\OrderRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Response\Handler\Door\ValidateInput;
use JardisSupport\Response\Handler\Failure\LogCause;
use JardisSupport\Response\Handler\Failure\MapThrowable;
use JardisSupport\Response\Handler\Failure\NotFoundPayload;
use JardisSupport\Response\Handler\Failure\ValidationPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use JardisSupport\Validation\ObjectValidator;
use Throwable;
use Ecommerce\Sales\Model\Order\Command\SetCustomer as CommandSetCustomer;
use Ecommerce\Sales\Model\Order\Query\OrderByOrderNumber as QueryOrder;

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
        /** @var CommandSetCustomer $setCustomer */
        $setCustomer = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(SetCustomerValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($setCustomer);
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
            $query = $this->handle(
                QueryOrder::class,
                tenant: $setCustomer->tenant,
                orderNumber: $setCustomer->orderNumber
            );
            /** @var Order|null $handler */
            $handler = $this->handle(OrderRepository::class)->getOrderByOrderNumber($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['orderNumber' => $setCustomer->orderNumber]
                        ),
                        null,
                        ''
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildSetCustomerData::class)($setCustomer);

            $handler->setCustomer($entityData);
            $handler->setAddress($entityData['address']);

            $persistResult = $this->handle(OrderRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'order',
                'id' => $handler->getData()->getId(),
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

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Order');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'SetCustomer');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
