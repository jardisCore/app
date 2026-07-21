<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Data;

/**
 * ShipmentStatus enum
 *
 * Generated from database ENUM type
 */
enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Picking = 'picking';
    case Packed = 'packed';
    case Shipped = 'shipped';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Failed = 'failed';
}
