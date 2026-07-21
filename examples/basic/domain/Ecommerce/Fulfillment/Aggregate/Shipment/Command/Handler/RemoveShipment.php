<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Aggregate\Shipment\Event\ShipmentRemoved;
use Ecommerce\Fulfillment\Aggregate\Shipment\Repository\ShipmentRepository;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\RemoveShipment as CommandRemoveShipment;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentByIdentifier as QueryShipment;

/**
 * Command endpoint: RemoveShipment
 */
class RemoveShipment extends EcommerceContext
{
    /**
     * Removes the Shipment aggregate.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandRemoveShipment $removeShipment */
            $removeShipment = $this->payload();

            $query = $this->handle(QueryShipment::class, identifier: $removeShipment->shipmentIdentifier);
            /** @var Shipment $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);

            $handler->remove();

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['shipmentIdentifier' => $removeShipment->shipmentIdentifier]);

            $event = $this->handle(ShipmentRemoved::class, shipmentIdentifier: $handler->getData()->getIdentifier(), occurredAt: new DateTimeImmutable());
            $this->result()->addEvent($event, EventScope::Internal);

            return $this->handle(DomainResponseTransformer::class)->transform($this->result());
        } catch (ValidationException $e) {
            $this->result()->addError($e->getMessage());
            return $this->handle(DomainResponseTransformer::class)->transform($this->result(), ResponseStatus::ValidationError);
        } catch (\Throwable $e) {
            $this->result()->addError($e->getMessage());
            return $this->handle(DomainResponseTransformer::class)->transform($this->result(), ResponseStatus::InternalError);
        }
    }
}
