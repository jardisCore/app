<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildSetShipmentAddressData;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidateSetShipmentAddress;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentShipmentAddressUpdated;
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
use Throwable;
use Ecommerce\Fulfillment\Model\Shipment\Command\SetShipmentAddress as CommandSetShipmentAddress;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIdentifier as QueryShipment;

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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandSetShipmentAddress $setShipmentAddress */
            $setShipmentAddress = $this->payload();

            $this->handle(ValidateSetShipmentAddress::class)($setShipmentAddress);

            $query = $this->handle(QueryShipment::class, identifier: $setShipmentAddress->shipmentIdentifier);
            /** @var Shipment|null $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['shipmentIdentifier' => $setShipmentAddress->shipmentIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildSetShipmentAddressData::class)($setShipmentAddress);

            $handler->setShipmentAddress($entityData);

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'shipment',
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
