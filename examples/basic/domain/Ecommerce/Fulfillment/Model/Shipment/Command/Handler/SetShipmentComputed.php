<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildSetShipmentComputedData;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildSetShipmentComputedExpected;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\SetShipmentComputedValidatorRegistry;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentComputedUpdated;
use Ecommerce\Fulfillment\Model\Shipment\Repository\ShipmentRepository;
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
use RuntimeException;
use Throwable;
use Ecommerce\Fulfillment\Model\Shipment\Command\SetShipmentComputed as CommandSetShipmentComputed;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIdentifier as QueryShipment;

/**
 * Command endpoint: SetShipmentComputed
 *
 * Operation: set Shipment computed (root), family-internal
 */
class SetShipmentComputed extends EcommerceContext
{
    /**
     * Updates the root Shipment entity.
     *
     * @param array<string, mixed> $expected Optional read basis (CAS): outer field name => value the caller read
     * @return DomainResponseInterface
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(array $expected = []): DomainResponseInterface
    {
        /** @var CommandSetShipmentComputed $setShipmentComputed */
        $setShipmentComputed = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(SetShipmentComputedValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($setShipmentComputed);
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
            $query = $this->handle(QueryShipment::class, identifier: $setShipmentComputed->shipmentIdentifier);
            /** @var Shipment|null $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['shipmentIdentifier' => $setShipmentComputed->shipmentIdentifier]
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

            $entityData = $this->handle(BuildSetShipmentComputedData::class)($setShipmentComputed);

            if ($expected !== []) {
                $handler->overlayReadBasis($this->handle(BuildSetShipmentComputedExpected::class)($expected));
            }

            $handler->setShipment($entityData);

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'shipment',
                'id' => $handler->getData()->getId(),
                'shipmentIdentifier' => $setShipmentComputed->shipmentIdentifier,
            ]);

            $event = $this->handle(
                ShipmentComputedUpdated::class,
                shipmentIdentifier: $handler->getData()->getIdentifier(),
                orderNumber: $handler->getData()->getOrderNumber(),
                customerIdentifier: $handler->getData()->getCustomerIdentifier(),
                carrier: $handler->getData()->getCarrier()?->value ?? throw new RuntimeException('carrier is required'),
                serviceLevel: $handler->getData()->getServiceLevel()?->value
                    ?? throw new RuntimeException('serviceLevel is required'),
                trackingNumber: $handler->getData()->getTrackingNumber(),
                status: $handler->getData()->getStatus()?->value ?? throw new RuntimeException('status is required'),
                weightGrams: $handler->getData()->getWeightGrams(),
                packageCount: $handler->getData()->getPackageCount(),
                insuranceValue: $handler->getData()->getInsuranceValue(),
                estimatedDelivery: $handler->getData()->getEstimatedDelivery() !== null
                    ? DateTimeImmutable::createFromInterface($handler->getData()->getEstimatedDelivery())
                    : null,
                shippedAt: $handler->getData()->getShippedAt() !== null
                    ? DateTimeImmutable::createFromInterface($handler->getData()->getShippedAt())
                    : null,
                deliveredAt: $handler->getData()->getDeliveredAt() !== null
                    ? DateTimeImmutable::createFromInterface($handler->getData()->getDeliveredAt())
                    : null,
                note: $handler->getData()->getNote(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Shipment');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'SetShipmentComputed');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
