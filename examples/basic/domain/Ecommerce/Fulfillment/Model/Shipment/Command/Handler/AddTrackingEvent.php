<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildAddTrackingEventData;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\AddTrackingEventValidatorRegistry;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentTrackingEventAdded;
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
use Ecommerce\Fulfillment\Model\Shipment\Command\AddTrackingEvent as CommandAddTrackingEvent;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIdentifier as QueryShipment;

/**
 * Command endpoint: AddTrackingEvent
 *
 * Operation: add TrackingEvent (many)
 */
class AddTrackingEvent extends EcommerceContext
{
    /**
     * Adds the TrackingEvent entity.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandAddTrackingEvent $addTrackingEvent */
        $addTrackingEvent = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(AddTrackingEventValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($addTrackingEvent);
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
            $query = $this->handle(QueryShipment::class, identifier: $addTrackingEvent->shipmentIdentifier);
            /** @var Shipment|null $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['shipmentIdentifier' => $addTrackingEvent->shipmentIdentifier]
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

            $entityData = $this->handle(BuildAddTrackingEventData::class)($addTrackingEvent);

            $handler->addTrackingEvent($entityData);

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $trackingEvent = $handler->getData()->getTrackingEvent();
            $this->result()->setData([
                '@type' => 'shipment',
                'id' => $handler->getData()->getId(),
                'shipmentIdentifier' => $addTrackingEvent->shipmentIdentifier,
                'trackingEventId' => $trackingEvent[array_key_last($trackingEvent)]->getId(),
            ]);

            $trackingEventForEvent = $handler->getData()->getTrackingEvent();
            $addedEntity = !empty($trackingEventForEvent)
                ? $trackingEventForEvent[array_key_last($trackingEventForEvent)]
                : null;

            $event = $this->handle(
                ShipmentTrackingEventAdded::class,
                shipmentIdentifier: $handler->getData()->getIdentifier(),
                trackingEventId: $addedEntity?->getId(),
                eventCode: $addedEntity?->getEventCode(),
                status: $addedEntity?->getStatus()?->value ?? throw new RuntimeException('status is required'),
                location: $addedEntity?->getLocation(),
                postalCode: $addedEntity?->getPostalCode(),
                detail: $addedEntity?->getDetail(),
                occurredAt: new DateTimeImmutable(),
                reportedAt: DateTimeImmutable::createFromInterface($addedEntity?->getReportedAt())
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result());
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Shipment');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'AddTrackingEvent');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
