<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildAddTrackingEventData;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidateAddTrackingEvent;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentTrackingEventAdded;
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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddTrackingEvent $addTrackingEvent */
            $addTrackingEvent = $this->payload();

            $this->handle(ValidateAddTrackingEvent::class)($addTrackingEvent);

            $query = $this->handle(QueryShipment::class, identifier: $addTrackingEvent->shipmentIdentifier);
            /** @var Shipment|null $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['shipmentIdentifier' => $addTrackingEvent->shipmentIdentifier]
                        )
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
