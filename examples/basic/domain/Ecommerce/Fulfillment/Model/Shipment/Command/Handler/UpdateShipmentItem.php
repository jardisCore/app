<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildUpdateShipmentItemData;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\UpdateShipmentItemValidatorRegistry;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentShipmentItemUpdated;
use Ecommerce\Fulfillment\Model\Shipment\Repository\ShipmentRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Response\Handler\Door\ValidateInput;
use JardisSupport\Response\Handler\Failure\LogCause;
use JardisSupport\Response\Handler\Failure\MapThrowable;
use JardisSupport\Response\Handler\Failure\NotFoundPayload;
use JardisSupport\Response\Handler\Failure\ValidationPayload;
use JardisSupport\Response\Handler\Response\BuildResponseData;
use JardisSupport\Validation\ObjectValidator;
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
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandUpdateShipmentItem $updateShipmentItem */
        $updateShipmentItem = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(UpdateShipmentItemValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($updateShipmentItem);
        if ($findings->fields !== []) {
            foreach ($findings->errors as $error) {
                $this->result()->addError($error);
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))(
                    ResponseStatus::ValidationError,
                    ($this->handle(ValidationPayload::class))('validation', $findings->fields),
                    null,
                    ''
                )
            );

            return ($this->transformer())($this->result(), ResponseStatus::ValidationError);
        }
        $connection = ($this->handle(ResolveConnection::class))($this->resource()->dbConnection());
        $owner = $connection?->inTransaction() !== true;

        try {
            $query = $this->handle(
                QueryShipment::class,
                tenant: $updateShipmentItem->tenant,
                identifier: $updateShipmentItem->shipmentIdentifier
            );
            /** @var Shipment|null $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['shipmentIdentifier' => $updateShipmentItem->shipmentIdentifier]
                        ),
                        null,
                        ''
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
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['shipmentItemIdentifier' => $updateShipmentItem->shipmentItemIdentifier]
                        ),
                        null,
                        ''
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
                'id' => $handler->getData()->getId(),
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
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Shipment');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'UpdateShipmentItem');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
