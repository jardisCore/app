<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action\BuildAddShipmentItemData;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\AddShipmentItemValidatorRegistry;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentShipmentItemAdded;
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
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var CommandAddShipmentItem $addShipmentItem */
        $addShipmentItem = $this->payload();
        $validator = $this->handle(
            ObjectValidator::class,
            registry: ($this->handle(AddShipmentItemValidatorRegistry::class))(),
            fieldPaths: true
        );
        $findings = ($this->handle(ValidateInput::class, validator: $validator))($addShipmentItem);
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
                tenant: $addShipmentItem->tenant,
                identifier: $addShipmentItem->shipmentIdentifier
            );
            /** @var Shipment|null $handler */
            $handler = $this->handle(ShipmentRepository::class)->getShipmentByIdentifier($query);
            if ($handler === null) {
                $this->result()->setData(
                    ($this->handle(BuildResponseData::class))(
                        ResponseStatus::NotFound,
                        ($this->handle(NotFoundPayload::class))(
                            'notFound',
                            ['shipmentIdentifier' => $addShipmentItem->shipmentIdentifier]
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

            $entityData = $this->handle(BuildAddShipmentItemData::class)($addShipmentItem);

            $handler->addShipmentItem($entityData);

            $persistResult = $this->handle(ShipmentRepository::class)->persist($handler);
            $this->result()->addResult($persistResult);

            $shipmentItem = $handler->getData()->getShipmentItem();
            $this->result()->setData([
                '@type' => 'shipment',
                'id' => $handler->getData()->getId(),
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
        } catch (Throwable $e) {
            $failure = ($this->handle(MapThrowable::class))($e, $owner, 'Shipment');
            if ($owner && $failure->status->value >= ResponseStatus::InternalError->value) {
                ($this->handle(LogCause::class, logger: $this->resource()->logger()))($e, 'AddShipmentItem');
            }
            $this->result()->setData(
                ($this->handle(BuildResponseData::class))($failure->status, $failure->response, null, '')
            );

            return ($this->transformer())($this->result(), $failure->status);
        }
    }
}
