<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Command\Shipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\ShipmentAddress;
use Ecommerce\Fulfillment\Model\Shipment\Command\ShipmentItem;
use Ecommerce\Fulfillment\Model\Shipment\Command\TrackingEvent;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ShipmentAddressValidator;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ShipmentItemValidator;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ShipmentValidator;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\TrackingEventValidator;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;
use Ecommerce\Fulfillment\Model\Shipment\Command\Shipment as CommandShipment;

/**
 * Validates the CommandShipment command DTO graph.
 *
 * Sets up ObjectValidator with ValidatorRegistry for recursive validation
 * of the complete DTO tree (root + nested ONE + nested MANY).
 */
class ValidateCreateShipment extends EcommerceContext
{
    /**
     * Validates the command DTO.
     *
     * @param CommandShipment $dto Command DTO
     * @throws ValidationException If validation fails
     * @throws Throwable
     */
    public function __invoke(CommandShipment $dto): void
    {
        $registry = new ValidatorRegistry();
        $registry->register(Shipment::class, ($this->handle(ShipmentValidator::class))());
        $registry->register(ShipmentAddress::class, ($this->handle(ShipmentAddressValidator::class))());
        $registry->register(ShipmentItem::class, ($this->handle(ShipmentItemValidator::class))());
        $registry->register(TrackingEvent::class, ($this->handle(TrackingEventValidator::class))());

        $validator = new ObjectValidator($registry, indexedLists: true);
        $result = $validator->validate($dto);
        $errors = $this->filterEmptyErrors($result->getErrors());
        $kinds = $this->filterEmptyErrors($result->getKinds());

        if (!empty($errors)) {
            throw new ValidationException($errors, $kinds);
        }
    }

    /**
     * Recursively filters empty error arrays from validation results.
     *
     * @param array<string, mixed> $errors
     * @return array<string, mixed>
     */
    private function filterEmptyErrors(array $errors): array
    {
        $filtered = [];

        foreach ($errors as $key => $value) {
            if (is_array($value)) {
                $nested = $this->filterEmptyErrors($value);
                if (!empty($nested)) {
                    $filtered[$key] = $nested;
                }
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}
