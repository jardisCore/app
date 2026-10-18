<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildUpdateTrackingEventData;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidateUpdateTrackingEvent;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentTrackingEventUpdated;
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
use Ecommerce\Fulfillment\Model\Shipment\Command\UpdateTrackingEvent as CommandUpdateTrackingEvent;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIdentifier as QueryShipment;

/**
 * Command endpoint: UpdateTrackingEvent
 *
 * Operation: update TrackingEvent (many)
 */
class UpdateTrackingEvent extends EcommerceContext
{
    /**
     * Updates a TrackingEvent in the collection.
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
            /** @var CommandUpdateTrackingEvent $updateTrackingEvent */
            $updateTrackingEvent = $this->payload();

            $this->handle(ValidateUpdateTrackingEvent::class)($updateTrackingEvent);

            $query = $this->handle(QueryShipment::class, identifier: $updateTrackingEvent->shipmentIdentifier);
            /** @var Shipment|null $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['shipmentIdentifier' => $updateTrackingEvent->shipmentIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildUpdateTrackingEventData::class)($updateTrackingEvent);

            $items = $handler->getData()->getTrackingEvent();
            $found = false;
            foreach ($items as $item) {
                if ($item->getId() === $updateTrackingEvent->trackingEventId) {
                    $handler->updateTrackingEvent($item->getId(), $entityData);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['trackingEventId' => $updateTrackingEvent->trackingEventId]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData([
                '@type' => 'shipment',
                'shipmentIdentifier' => $updateTrackingEvent->shipmentIdentifier,
                'trackingEventId' => $updateTrackingEvent->trackingEventId,
            ]);

            $event = $this->handle(
                ShipmentTrackingEventUpdated::class,
                shipmentIdentifier: $handler->getData()->getIdentifier(),
                trackingEventId: $updateTrackingEvent->trackingEventId,
                eventCode: $updateTrackingEvent->eventCode,
                status: $updateTrackingEvent->status,
                location: $updateTrackingEvent->location,
                postalCode: $updateTrackingEvent->postalCode,
                detail: $updateTrackingEvent->detail,
                reportedAt: $updateTrackingEvent->reportedAt,
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
