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
use Ecommerce\Fulfillment\Model\Shipment\Repository\ConcurrencyConflictException;
use Ecommerce\Fulfillment\Model\Shipment\Repository\ShipmentRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Workflow\Handler\Failure\ConcurrencyConflictPayload;
use JardisSupport\Workflow\Handler\Failure\ValidationPayload;
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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws Throwable
     * @throws TransientConflictException
     * @throws ValidationException
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

            $this->result()->setData([
                '@type' => 'shipment',
                'shipmentIdentifier' => $handler->getData()->getIdentifier(),
                'shipmentItemIdentifiers' => array_map(
                    fn($e) => $e->getIdentifier(),
                    $handler->getData()->getShipmentItem()
                ),
            ]);

            $event = $this->handle(
                ShipmentCreated::class,
                shipmentIdentifier: $handler->getData()->getIdentifier(),
                occurredAt: new DateTimeImmutable()
            );
            $this->result()->addEvent($event, EventScope::Internal);

            return ($this->transformer())($this->result(), ResponseStatus::Created);
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
