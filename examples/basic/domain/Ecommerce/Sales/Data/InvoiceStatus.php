<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Data;

/**
 * InvoiceStatus enum
 *
 * Generated from database ENUM type
 */
enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
