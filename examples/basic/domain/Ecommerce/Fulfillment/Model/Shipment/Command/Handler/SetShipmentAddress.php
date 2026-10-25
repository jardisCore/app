<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildSetShipmentAddressData;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\SetShipmentAddressValidatorRegistry;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentShipmentAddressUpdated;
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
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandSetShipmentAddress $setShipmentAddress */
        $setShipmentAddress = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(SetShipmentAddressValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($setShipmentAddress);
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
                QueryShipment::class,
                tenant: $setShipmentAddress->tenant,
                identifier: $setShipmentAddress->shipmentIdentifier
            );
            /** @var Shipment|null $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['shipmentIdentifier' => $setShipmentAddress->shipmentIdentifier]
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

            $entityData = $this->handle(BuildSetShipmentAddressData::class)($setShipmentAddress);

            $handler->setShipmentAddress($entityData);

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'shipment',
                'id' => $handler->getData()->getId(),
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
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Shipment');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'SetShipmentAddress');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
