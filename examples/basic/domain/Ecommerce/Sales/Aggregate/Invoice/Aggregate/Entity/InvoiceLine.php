<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use Ecommerce\Sales\Entity\InvoiceLine as EntityInvoiceLine;

/**
 * Aggregate entity: InvoiceLine
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'InvoiceLine')]
class InvoiceLine extends EntityInvoiceLine
{
}
