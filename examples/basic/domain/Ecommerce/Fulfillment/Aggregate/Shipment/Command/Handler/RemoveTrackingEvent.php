<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Aggregate\Shipment\Event\ShipmentTrackingEventRemoved;
use Ecommerce\Fulfillment\Aggregate\Shipment\Repository\ShipmentRepository;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use RuntimeException;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\RemoveTrackingEvent as CommandRemoveTrackingEvent;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentByIdentifier as QueryShipment;

/**
 * Command endpoint: RemoveTrackingEvent
 *
 * Operation: remove TrackingEvent (many)
 */
class RemoveTrackingEvent extends EcommerceContext
{
    /**
     * Removes a TrackingEvent from the collection.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandRemoveTrackingEvent $removeTrackingEvent */
            $removeTrackingEvent = $this->payload();

            $query = $this->handle(QueryShipment::class, identifier: $removeTrackingEvent->shipmentIdentifier);
            /** @var Shipment $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);

            $items = $handler->getData()->getTrackingEvent();
            $found = false;
            foreach ($items as $item) {
                if ($item->getId() === $removeTrackingEvent->trackingEventId) {
                    $handler->removeTrackingEvent($item->getId());
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new RuntimeException("TrackingEvent not found: " . $removeTrackingEvent->trackingEventId);
            }

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['shipmentIdentifier' => $removeTrackingEvent->shipmentIdentifier]);

            $event = $this->handle(
                ShipmentTrackingEventRemoved::class,
                shipmentIdentifier: $handler->getData()->getIdentifier(),
                trackingEventId: $removeTrackingEvent->trackingEventId,
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
