<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\HydrateCreateShipmentEntities;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidateCreateShipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentCreated;
use Ecommerce\Fulfillment\Model\Shipment\Repository\ShipmentRepository;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Fulfillment\Model\Shipment\Command\Shipment as CommandShipment;

/**
 * Command endpoint: CreateShipment
 *
 * Creates a new Shipment aggregate
 */
class CreateShipment extends EcommerceContext
{
    /**
     * Creates a new Shipment.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandShipment $shipment */
            $shipment = $this->payload();

            /** @var Shipment $handler */
            $handler = $this->handle(ShipmentRepository::class)->createNew();
            $this->handle(ValidateCreateShipment::class)($shipment);
            $this->handle(HydrateCreateShipmentEntities::class)($handler, $shipment);

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['shipmentIdentifier' => $handler->getData()->getIdentifier()]);

            $event = $this->handle(ShipmentCreated::class, shipmentIdentifier: $handler->getData()->getIdentifier(), occurredAt: new DateTimeImmutable());
            $this->result()->addEvent($event, EventScope::Internal);

            return $this->handle(DomainResponseTransformer::class)->transform($this->result(), ResponseStatus::Created);
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
