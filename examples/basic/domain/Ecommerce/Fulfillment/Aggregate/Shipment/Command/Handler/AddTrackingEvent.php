<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Handler\Action\BuildAddTrackingEventData;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation\ValidateAddTrackingEvent;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Aggregate\Shipment\Event\ShipmentTrackingEventAdded;
use Ecommerce\Fulfillment\Aggregate\Shipment\Repository\ShipmentRepository;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\AddTrackingEvent as CommandAddTrackingEvent;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentByIdentifier as QueryShipment;

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
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddTrackingEvent $addTrackingEvent */
            $addTrackingEvent = $this->payload();

            $query = $this->handle(QueryShipment::class, identifier: $addTrackingEvent->shipmentIdentifier);
            /** @var Shipment $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);

            $this->handle(ValidateAddTrackingEvent::class)($addTrackingEvent);
            $entityData = $this->handle(BuildAddTrackingEventData::class)($addTrackingEvent);

            $handler->addTrackingEvent($entityData);

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $trackingEvent = $handler->getData()->getTrackingEvent();
            $this->result()->setData([
                'shipmentIdentifier' => $addTrackingEvent->shipmentIdentifier,
                'trackingEventId' => $trackingEvent[array_key_last($trackingEvent)]->getId(),
            ]);

            $trackingEventForEvent = $handler->getData()->getTrackingEvent();
            $addedEntity = !empty($trackingEventForEvent) ? $trackingEventForEvent[array_key_last($trackingEventForEvent)] : null;

            $event = $this->handle(
                ShipmentTrackingEventAdded::class,
                shipmentIdentifier: $handler->getData()->getIdentifier(),
                trackingEventId: $addedEntity?->getId(),
                eventCode: $addedEntity?->getEventCode(),
                status: $addedEntity?->getStatus(),
                location: $addedEntity?->getLocation(),
                postalCode: $addedEntity?->getPostalCode(),
                detail: $addedEntity?->getDetail(),
                occurredAt: new DateTimeImmutable(),
                reportedAt: DateTimeImmutable::createFromInterface($addedEntity?->getReportedAt())
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
