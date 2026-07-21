<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Aggregate\Action;

use Ecommerce\EcommerceContext;
use RuntimeException;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Entity\Invoice as InvoiceAggregate;

/**
 * Action: RemoveInvoiceLine
 */
class RemoveInvoiceLine extends EcommerceContext
{
    /**
     * Removes an InvoiceLine from the invoiceLine collection.
     *
     * @param InvoiceAggregate $aggregate
     * @param int $id
     * @return array<array{string, object|null}> Entities to track for deletion
     * @throws RuntimeException
     */
    public function __invoke(InvoiceAggregate $aggregate, int $id): array
    {
        $removals = [];
        $collection = $aggregate->getInvoiceLine();

        foreach ($collection as $item) {
            $primaryKey = $item::PRIMARY_KEY;
            $getter = 'get' . ucfirst($primaryKey);
            $itemId = $item->$getter();
            if ($itemId !== null && $itemId === $id) {
                if (count($collection) <= 1) {
                    throw new RuntimeException('Cannot remove the last InvoiceLine: at least one is required.');
                }
                $removals[] = ['invoiceLine', $item];
                $aggregate->removeInvoiceLine($item);
                break;
            }
        }
        return $removals;
    }
}
