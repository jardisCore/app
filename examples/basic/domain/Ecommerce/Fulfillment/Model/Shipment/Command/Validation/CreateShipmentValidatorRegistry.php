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
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandShipment command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class CreateShipmentValidatorRegistry extends EcommerceContext
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
        $registry->register(Shipment::class, ($this->handle(ShipmentValidator::class))());
        $registry->register(ShipmentAddress::class, ($this->handle(ShipmentAddressValidator::class))());
        $registry->register(ShipmentItem::class, ($this->handle(ShipmentItemValidator::class))());
        $registry->register(TrackingEvent::class, ($this->handle(TrackingEventValidator::class))());

        return $registry;
    }
}
