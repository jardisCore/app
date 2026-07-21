<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Data;

/**
 * OrderStatus enum
 *
 * Generated from database ENUM type
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
