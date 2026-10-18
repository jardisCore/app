<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildUpdateShipmentData;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildUpdateShipmentExpected;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidateUpdateShipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentUpdated;
use Ecommerce\Fulfillment\Model\Shipment\Repository\ConcurrencyConflictException;
use Ecommerce\Fulfillment\Model\Shipment\Repository\ShipmentRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Workflow\Handler\Failure\ConcurrencyConflictPayload;
use JardisSupport\Workflow\Handler\Failure\NotFoundPayload;
use JardisSupport\Workflow\Handler\Failure\ValidationPayload;
use RuntimeException;
use Throwable;
use Ecommerce\Fulfillment\Model\Shipment\Command\UpdateShipment as CommandUpdateShipment;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIdentifier as QueryShipment;

/**
 * Command endpoint: UpdateShipment
 *
 * Operation: update Shipment (root)
 */
class UpdateShipment extends EcommerceContext
{
    /**
     * Updates the root Shipment entity.
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
            /** @var CommandUpdateShipment $updateShipment */
            $updateShipment = $this->payload();

            $this->handle(ValidateUpdateShipment::class)($updateShipment);

            $query = $this->handle(QueryShipment::class, identifier: $updateShipment->shipmentIdentifier);
            /** @var Shipment|null $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['shipmentIdentifier' => $updateShipment->shipmentIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildUpdateShipmentData::class)($updateShipment);

            if ($expected !== []) {
                $handler->overlayReadBasis($this->handle(BuildUpdateShipmentExpected::class)($expected));
            }

            $handler->setShipment($entityData);

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'shipment',
                'shipmentIdentifier' => $updateShipment->shipmentIdentifier,
            ]);

            $event = $this->handle(
                ShipmentUpdated::class,
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
