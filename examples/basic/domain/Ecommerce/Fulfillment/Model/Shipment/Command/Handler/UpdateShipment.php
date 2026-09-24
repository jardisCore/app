<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildUpdateShipmentData;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidateUpdateShipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentUpdated;
use Ecommerce\Fulfillment\Model\Shipment\Repository\ShipmentRepository;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
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
     * @return DomainResponseInterface
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandUpdateShipment $updateShipment */
            $updateShipment = $this->payload();

            $query = $this->handle(QueryShipment::class, identifier: $updateShipment->shipmentIdentifier);
            /** @var Shipment $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);

            $this->handle(ValidateUpdateShipment::class)($updateShipment);
            $entityData = $this->handle(BuildUpdateShipmentData::class)($updateShipment);

            $handler->setShipment($entityData);

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['shipmentIdentifier' => $updateShipment->shipmentIdentifier]);

            $event = $this->handle(
                ShipmentUpdated::class,
                shipmentIdentifier: $handler->getData()->getIdentifier(),
                orderNumber: $handler->getData()->getOrderNumber(),
                customerIdentifier: $handler->getData()->getCustomerIdentifier(),
                carrier: $handler->getData()->getCarrier()?->value ?? throw new RuntimeException('carrier is required'),
                serviceLevel: $handler->getData()->getServiceLevel()?->value ?? throw new RuntimeException('serviceLevel is required'),
                trackingNumber: $handler->getData()->getTrackingNumber(),
                status: $handler->getData()->getStatus()?->value ?? throw new RuntimeException('status is required'),
                weightGrams: $handler->getData()->getWeightGrams(),
                packageCount: $handler->getData()->getPackageCount(),
                insuranceValue: $handler->getData()->getInsuranceValue(),
                estimatedDelivery: $handler->getData()->getEstimatedDelivery() !== null ? DateTimeImmutable::createFromInterface($handler->getData()->getEstimatedDelivery()) : null,
                shippedAt: $handler->getData()->getShippedAt() !== null ? DateTimeImmutable::createFromInterface($handler->getData()->getShippedAt()) : null,
                deliveredAt: $handler->getData()->getDeliveredAt() !== null ? DateTimeImmutable::createFromInterface($handler->getData()->getDeliveredAt()) : null,
                note: $handler->getData()->getNote(),
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
