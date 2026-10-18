<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Data;

/**
 * How the invoice is handed to the customer. Comment guard * / must never break the generated docblock.
 *
 * InvoiceDeliveryChannel enum
 *
 * Generated from database ENUM type
 */
enum InvoiceDeliveryChannel: string
{
    case Email = 'email';
    case Post = 'post';
    case Portal = 'portal';
}
