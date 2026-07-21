<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Handler\Action\BuildSetShipmentAddressData;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation\ValidateSetShipmentAddress;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Aggregate\Shipment\Event\ShipmentShipmentAddressUpdated;
use Ecommerce\Fulfillment\Aggregate\Shipment\Repository\ShipmentRepository;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\SetShipmentAddress as CommandSetShipmentAddress;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentByIdentifier as QueryShipment;

/**
 * Command endpoint: SetShipmentAddress
 *
 * Operation: set ShipmentAddress (one)
 */
class SetShipmentAddress extends EcommerceContext
{
    /**
     * Sets/replaces the ShipmentAddress entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandSetShipmentAddress $setShipmentAddress */
            $setShipmentAddress = $this->payload();

            $query = $this->handle(QueryShipment::class, identifier: $setShipmentAddress->shipmentIdentifier);
            /** @var Shipment $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);

            $this->handle(ValidateSetShipmentAddress::class)($setShipmentAddress);
            $entityData = $this->handle(BuildSetShipmentAddressData::class)($setShipmentAddress);

            $handler->setShipmentAddress($entityData);

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                'shipmentIdentifier' => $setShipmentAddress->shipmentIdentifier,
                'shipmentAddressId' => $handler->getData()->getShipmentAddress()?->getId(),
            ]);

            $event = $this->handle(
                ShipmentShipmentAddressUpdated::class,
                shipmentIdentifier: $handler->getData()->getIdentifier(),
                recipientName: $handler->getData()->getShipmentAddress()?->getRecipientName(),
                company: $handler->getData()->getShipmentAddress()?->getCompany(),
                street: $handler->getData()->getShipmentAddress()?->getStreet(),
                street2: $handler->getData()->getShipmentAddress()?->getStreet2(),
                city: $handler->getData()->getShipmentAddress()?->getCity(),
                postalCode: $handler->getData()->getShipmentAddress()?->getPostalCode(),
                state: $handler->getData()->getShipmentAddress()?->getState(),
                country: $handler->getData()->getShipmentAddress()?->getCountry(),
                phone: $handler->getData()->getShipmentAddress()?->getPhone(),
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
