<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Data;

/**
 * ProductTaxClass enum
 *
 * Generated from database ENUM type
 */
enum ProductTaxClass: string
{
    case Standard = 'standard';
    case Reduced = 'reduced';
    case Zero = 'zero';
}
