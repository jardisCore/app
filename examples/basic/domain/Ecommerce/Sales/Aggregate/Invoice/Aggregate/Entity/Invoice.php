<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use JardisSupport\Data\Attribute\Relation;
use Ecommerce\Sales\Entity\Invoice as EntityInvoice;

/**
 * Root aggregate: Invoice
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'Invoice', root: true)]
class Invoice extends EntityInvoice
{
    /**
     * @var InvoiceLine[]
     */
    #[Relation(type: 'many', target: InvoiceLine::class)]
    private array $invoiceLine = [];

    /**
     * @return InvoiceLine[]
     */
    public function getInvoiceLine(): array
    {
        return $this->invoiceLine;
    }

    public function addInvoiceLine(InvoiceLine $invoiceLine): self
    {
        $this->invoiceLine[] = $invoiceLine;
        return $this;
    }

    public function removeInvoiceLine(InvoiceLine $invoiceLine): self
    {
        $this->invoiceLine = array_values(
            array_filter($this->invoiceLine, fn($existing) => $existing !== $invoiceLine)
        );
        return $this;
    }
}
