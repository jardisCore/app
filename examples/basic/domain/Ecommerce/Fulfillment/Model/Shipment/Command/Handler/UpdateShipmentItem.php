<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildUpdateShipmentItemData;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidateUpdateShipmentItem;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentShipmentItemUpdated;
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
use Ecommerce\Fulfillment\Model\Shipment\Command\UpdateShipmentItem as CommandUpdateShipmentItem;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIdentifier as QueryShipment;

/**
 * Command endpoint: UpdateShipmentItem
 *
 * Operation: update ShipmentItem (many)
 */
class UpdateShipmentItem extends EcommerceContext
{
    /**
     * Updates a ShipmentItem in the collection.
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
            /** @var CommandUpdateShipmentItem $updateShipmentItem */
            $updateShipmentItem = $this->payload();

            $this->handle(ValidateUpdateShipmentItem::class)($updateShipmentItem);

            $query = $this->handle(QueryShipment::class, identifier: $updateShipmentItem->shipmentIdentifier);
            /** @var Shipment|null $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['shipmentIdentifier' => $updateShipmentItem->shipmentIdentifier]
                        )
                    )
                );

                return ($this->transformer())(
                    $this->result(),
                    ResponseStatus::NotFound
                );
            }

            $entityData = $this->handle(BuildUpdateShipmentItemData::class)($updateShipmentItem);

            $items = $handler->getData()->getShipmentItem();
            $found = false;
            foreach ($items as $item) {
                if ($item->getIdentifier() === $updateShipmentItem->shipmentItemIdentifier) {
                    $handler->updateShipmentItem($item->getId(), $entityData);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->result()->setData(
                    $this->failureData(
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['shipmentItemIdentifier' => $updateShipmentItem->shipmentItemIdentifier]
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
                'shipmentIdentifier' => $updateShipmentItem->shipmentIdentifier,
                'shipmentItemIdentifier' => $updateShipmentItem->shipmentItemIdentifier,
            ]);

            $event = $this->handle(
                ShipmentShipmentItemUpdated::class,
                shipmentIdentifier: $handler->getData()->getIdentifier(),
                shipmentItemIdentifier: $updateShipmentItem->shipmentItemIdentifier,
                productSku: $updateShipmentItem->productSku,
                productName: $updateShipmentItem->productName,
                quantity: $updateShipmentItem->quantity,
                weightGrams: $updateShipmentItem->weightGrams,
                isFragile: $updateShipmentItem->isFragile,
                serialNumber: $updateShipmentItem->serialNumber,
                lotNumber: $updateShipmentItem->lotNumber,
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
