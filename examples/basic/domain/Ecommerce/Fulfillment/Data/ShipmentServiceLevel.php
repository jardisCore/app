<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Data;

/**
 * ShipmentServiceLevel enum
 *
 * Generated from database ENUM type
 */
enum ShipmentServiceLevel: string
{
    case Standard = 'standard';
    case Express = 'express';
    case Overnight = 'overnight';
    case Economy = 'economy';
}
