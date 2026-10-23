<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Command\SetShipmentAddress;
use Ecommerce\Fulfillment\Model\Shipment\Command\Validation\ShipmentAddressValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandSetShipmentAddress command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class SetShipmentAddressValidatorRegistry extends EcommerceContext
{
    /**
     * Builds the registry.
     *
     * @return ValidatorRegistry
     * @throws Throwable
     */
    public function __invoke(): ValidatorRegistry
    {
        $registry = new ValidatorRegistry();
        $registry->register(SetShipmentAddress::class, ($this->handle(ShipmentAddressValidator::class))());

        return $registry;
    }
}
