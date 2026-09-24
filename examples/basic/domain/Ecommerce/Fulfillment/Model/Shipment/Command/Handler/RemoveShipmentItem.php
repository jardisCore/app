<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentShipmentItemRemoved;
use Ecommerce\Fulfillment\Model\Shipment\Repository\ShipmentRepository;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use RuntimeException;
use Throwable;
use Ecommerce\Fulfillment\Model\Shipment\Command\RemoveShipmentItem as CommandRemoveShipmentItem;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIdentifier as QueryShipment;

/**
 * Command endpoint: RemoveShipmentItem
 *
 * Operation: remove ShipmentItem (many)
 */
class RemoveShipmentItem extends EcommerceContext
{
    /**
     * Removes a ShipmentItem from the collection.
     *
     * @return DomainResponseInterface
     * @throws Exception
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CommandRemoveShipmentItem $removeShipmentItem */
            $removeShipmentItem = $this->payload();

            $query = $this->handle(QueryShipment::class, identifier: $removeShipmentItem->shipmentIdentifier);
            /** @var Shipment $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);

            $items = $handler->getData()->getShipmentItem();
            $found = false;
            foreach ($items as $item) {
                if ($item->getIdentifier() === $removeShipmentItem->shipmentItemIdentifier) {
                    $handler->removeShipmentItem($item->getId());
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new RuntimeException("ShipmentItem not found: " . $removeShipmentItem->shipmentItemIdentifier);
            }

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $this->result()->setData(['shipmentIdentifier' => $removeShipmentItem->shipmentIdentifier]);

            $event = $this->handle(
                ShipmentShipmentItemRemoved::class,
                shipmentIdentifier: $handler->getData()->getIdentifier(),
                shipmentItemIdentifier: $removeShipmentItem->shipmentItemIdentifier,
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
