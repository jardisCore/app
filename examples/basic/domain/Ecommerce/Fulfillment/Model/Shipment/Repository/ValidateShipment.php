<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Entity\Validation\ShipmentAddressValidator;
use Ecommerce\Fulfillment\Entity\Validation\ShipmentItemValidator;
use Ecommerce\Fulfillment\Entity\Validation\ShipmentValidator;
use Ecommerce\Fulfillment\Entity\Validation\TrackingEventValidator;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ValidationException;
use JardisSupport\Validation\CompositeFieldValidator;
use Throwable;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Shipment as ShipmentHandler;

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
     * @throws Throwable
     */
    public function __invoke(ShipmentHandler $handler): void
    {
        if ($handler->isMarkedForDeletion()) {
            return;
        }

        $errors = [];
        $kinds = [];
        $aggregate = $handler->getData();

        if (!empty($handler->getEntityData('shipment')['values'])) {
            $this->validateEntity($aggregate, ShipmentValidator::class, 'shipment', $errors, $kinds);
        }

        $shipmentAddress = $aggregate->getShipmentAddress();
        if ($shipmentAddress !== null && !empty($handler->getEntityData('shipmentAddress')['values'])) {
            $this->validateEntity(
                $shipmentAddress,
                ShipmentAddressValidator::class,
                'shipmentAddress',
                $errors,
                $kinds
            );
        }
        $shipmentItemCollection = $handler->getCollectionData('shipmentItem');
        foreach ($aggregate->getShipmentItem() as $index => $shipmentItem) {
            if (!empty($shipmentItemCollection[$index]['values'])) {
                $this->validateEntity(
                    $shipmentItem,
                    ShipmentItemValidator::class,
                    "shipmentItem[{$index}]",
                    $errors,
                    $kinds
                );
            }
        }
        $trackingEventCollection = $handler->getCollectionData('trackingEvent');
        foreach ($aggregate->getTrackingEvent() as $index => $trackingEvent) {
            if (!empty($trackingEventCollection[$index]['values'])) {
                $this->validateEntity(
                    $trackingEvent,
                    TrackingEventValidator::class,
                    "trackingEvent[{$index}]",
                    $errors,
                    $kinds
                );
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, $kinds, root: 'shipment');
        }
    }

    /**
     * Validates a single entity against its validator.
     *
     * @param object $entity The entity to validate
     * @param class-string $validatorClass Validator class name
     * @param string $entityName Entity identifier for error grouping
     * @param array<string, mixed> $errors Collected errors (by reference)
     * @param array<string, mixed> $kinds Collected validator kinds, same shape (by reference)
     * @throws Throwable
     */
    protected function validateEntity(
        object $entity,
        string $validatorClass,
        string $entityName,
        array &$errors,
        array &$kinds
    ): void {
        $validatorInstance = $this->handle($validatorClass);
        /** @var callable(): CompositeFieldValidator $validatorInstance */
        $validator = $validatorInstance();
        $result = $validator->validate($entity);

        if (!$result->isValid()) {
            $errors[$entityName] = $result->getErrors();
            $kinds[$entityName] = $result->getKinds();
        }
    }
}
