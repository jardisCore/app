<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Shipment;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\ShipmentAddress;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\ShipmentItem;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\TrackingEvent;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation\ShipmentAddressValidator;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation\ShipmentItemValidator;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation\ShipmentValidator;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Validation\TrackingEventValidator;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\ValidatorRegistry;
use JsonException;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Shipment as CommandShipment;

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
     * @throws JsonException
     * @throws Throwable
     */
    public function __invoke(CommandShipment $dto): void
    {
        $registry = new ValidatorRegistry();
        $registry->register(Shipment::class, ($this->handle(ShipmentValidator::class))());
        $registry->register(ShipmentAddress::class, ($this->handle(ShipmentAddressValidator::class))());
        $registry->register(ShipmentItem::class, ($this->handle(ShipmentItemValidator::class))());
        $registry->register(TrackingEvent::class, ($this->handle(TrackingEventValidator::class))());

        $validator = new ObjectValidator($registry);
        $result = $validator->validate($dto);
        $errors = $this->filterEmptyErrors($result->getErrors());

        if (!empty($errors)) {
            throw new ValidationException(json_encode($errors, JSON_THROW_ON_ERROR));
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
