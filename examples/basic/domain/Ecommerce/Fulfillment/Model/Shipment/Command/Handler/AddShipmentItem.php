<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildAddShipmentItemData;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidateAddShipmentItem;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentShipmentItemAdded;
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
use Ecommerce\Fulfillment\Model\Shipment\Command\AddShipmentItem as CommandAddShipmentItem;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIdentifier as QueryShipment;

/**
 * Command endpoint: AddShipmentItem
 *
 * Operation: add ShipmentItem (many)
 */
class AddShipmentItem extends EcommerceContext
{
    /**
     * Adds the ShipmentItem entity.
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
            /** @var CommandAddShipmentItem $addShipmentItem */
            $addShipmentItem = $this->payload();

            $this->handle(ValidateAddShipmentItem::class)($addShipmentItem);

            $query = $this->handle(QueryShipment::class, identifier: $addShipmentItem->shipmentIdentifier);
            /** @var Shipment|null $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['shipmentIdentifier' => $addShipmentItem->shipmentIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildAddShipmentItemData::class)($addShipmentItem);

            $handler->addShipmentItem($entityData);

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $shipmentItem = $handler->getData()->getShipmentItem();
            $this->result()->setData([
                '@type' => 'shipment',
                'shipmentIdentifier' => $addShipmentItem->shipmentIdentifier,
                'shipmentItemIdentifier' => $shipmentItem[array_key_last($shipmentItem)]->getIdentifier(),
            ]);

            $shipmentItemForEvent = $handler->getData()->getShipmentItem();
            $addedEntity = !empty($shipmentItemForEvent)
                ? $shipmentItemForEvent[array_key_last($shipmentItemForEvent)]
                : null;

            $event = $this->handle(
                ShipmentShipmentItemAdded::class,
                shipmentIdentifier: $handler->getData()->getIdentifier(),
                shipmentItemIdentifier: $addedEntity?->getIdentifier(),
                productSku: $addedEntity?->getProductSku(),
                productName: $addedEntity?->getProductName(),
                quantity: $addedEntity?->getQuantity(),
                weightGrams: $addedEntity?->getWeightGrams(),
                isFragile: $addedEntity?->getIsFragile(),
                serialNumber: $addedEntity?->getSerialNumber(),
                lotNumber: $addedEntity?->getLotNumber(),
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
