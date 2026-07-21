<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation\ValidationException;
use Ecommerce\Fulfillment\Entity\Validation\ShipmentAddressValidator;
use Ecommerce\Fulfillment\Entity\Validation\ShipmentItemValidator;
use Ecommerce\Fulfillment\Entity\Validation\ShipmentValidator;
use Ecommerce\Fulfillment\Entity\Validation\TrackingEventValidator;
use JsonException;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Shipment as ShipmentHandler;

/**
 * Validates Shipment aggregate entities before persistence.
 *
 * Validates all entities using their generated validators.
 * Called automatically by Repository before PersistAggregate.
 */
class ValidateShipment extends EcommerceContext
{
    /**
     * Validates all entities in the aggregate.
     *
     * @param ShipmentHandler $handler Aggregate handler
     * @throws ValidationException If validation fails
     * @throws JsonException
     * @throws Throwable
     */
    public function __invoke(ShipmentHandler $handler): void
    {
        if ($handler->isMarkedForDeletion()) {
            return;
        }

        $errors = [];
        $aggregate = $handler->getData();

        if (!empty($handler->getEntityData('shipment')['values'])) {
            $this->validateEntity($aggregate, ShipmentValidator::class, 'shipment', $errors);
        }

        $shipmentAddress = $aggregate->getShipmentAddress();
        if ($shipmentAddress !== null && !empty($handler->getEntityData('shipmentAddress')['values'])) {
            $this->validateEntity($shipmentAddress, ShipmentAddressValidator::class, 'shipmentAddress', $errors);
        }
        $shipmentItemCollection = $handler->getCollectionData('shipmentItem');
        foreach ($aggregate->getShipmentItem() as $index => $shipmentItem) {
            if (!empty($shipmentItemCollection[$index]['values'])) {
                $this->validateEntity($shipmentItem, ShipmentItemValidator::class, "shipmentItem[{$index}]", $errors);
            }
        }
        $trackingEventCollection = $handler->getCollectionData('trackingEvent');
        foreach ($aggregate->getTrackingEvent() as $index => $trackingEvent) {
            if (!empty($trackingEventCollection[$index]['values'])) {
                $this->validateEntity(
                    $trackingEvent,
                    TrackingEventValidator::class,
                    "trackingEvent[{$index}]",
                    $errors
                );
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
    }

    /**
     * Validates a single entity against its validator.
     *
     * @param object $entity The entity to validate
     * @param class-string $validatorClass Validator class name
     * @param string $entityName Entity identifier for error grouping
     * @param array<string, mixed> $errors Collected errors (by reference)
     * @throws Throwable
     */
    protected function validateEntity(
        object $entity,
        string $validatorClass,
        string $entityName,
        array &$errors
    ): void {
        $validator = ($this->handle($validatorClass))();
        $result = $validator->validate($entity);

        if (!$result->isValid()) {
            $errors[$entityName] = $result->getErrors();
        }
    }
}
