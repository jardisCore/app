<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Data;

/**
 * ShipmentCarrier enum
 *
 * Generated from database ENUM type
 */
enum ShipmentCarrier: string
{
    case Dhl = 'dhl';
    case Ups = 'ups';
    case Fedex = 'fedex';
    case Dpd = 'dpd';
    case Hermes = 'hermes';
    case Gls = 'gls';
}
