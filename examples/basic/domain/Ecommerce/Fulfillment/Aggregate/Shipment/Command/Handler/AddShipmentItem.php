<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Handler\Action\BuildAddShipmentItemData;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation\ValidateAddShipmentItem;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Aggregate\Shipment\Event\ShipmentShipmentItemAdded;
use Ecommerce\Fulfillment\Aggregate\Shipment\Repository\ShipmentRepository;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\AddShipmentItem as CommandAddShipmentItem;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentByIdentifier as QueryShipment;

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
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandAddShipmentItem $addShipmentItem */
            $addShipmentItem = $this->payload();

            $query = $this->handle(QueryShipment::class, identifier: $addShipmentItem->shipmentIdentifier);
            /** @var Shipment $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);

            $this->handle(ValidateAddShipmentItem::class)($addShipmentItem);
            $entityData = $this->handle(BuildAddShipmentItemData::class)($addShipmentItem);

            $handler->addShipmentItem($entityData);

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $shipmentItem = $handler->getData()->getShipmentItem();
            $this->result()->setData([
                'shipmentIdentifier' => $addShipmentItem->shipmentIdentifier,
                'shipmentItemIdentifier' => $shipmentItem[array_key_last($shipmentItem)]->getIdentifier(),
            ]);

            $shipmentItemForEvent = $handler->getData()->getShipmentItem();
            $addedEntity = !empty($shipmentItemForEvent) ? $shipmentItemForEvent[array_key_last($shipmentItemForEvent)] : null;

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
